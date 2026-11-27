// Compiles a Design System `tokens.json` into `tokens.css`, the same way the
// Design System artifact page does at runtime (rules: the type's
// `artifact-type/reference/format.md`, "tokens.css as compiled").
//
// Pure: object (or JSON text) in, CSS text out. File access and exit codes
// live in `tokens-css.mjs`.

const NAME = /^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/;
const HEX = /^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/;
const COLOR_FN = /^(?:rgb|rgba|hsl|hsla|oklch|oklab|lab|lch|color)\([0-9a-z.,%/ \-]{1,60}\)$/;
const ALIAS = /^\{([A-Za-z0-9][A-Za-z0-9_.-]{0,63})\}$/;
const FAMILY_KEY = /^(?!(?:__proto__|constructor|prototype)$)[A-Za-z][A-Za-z0-9_-]{0,31}$/;
const LENGTH = /^-?\d{1,4}(?:\.\d{1,6})?(?:px|rem|em|%)$|^0$/;
const FONT_WEIGHT = /^(?:[1-9]\d{0,2}|1000)(?: (?:[1-9]\d{0,2}|1000))?$/;
const FONT_STACK = /^[^;{}<>()\\\r\n\f]{1,200}$/;
const SHADOW = /^[a-z0-9 #%(),./+-]{1,400}$/;
const PLAIN_VALUE = /^[A-Za-z0-9 #%(),./+_-]{1,200}$/;
const DURATION = /^(?:\d{1,6}(?:\.\d{1,3})?|\.\d{1,3})m?s$/;
const EASING =
  /^(?:linear|ease|ease-in|ease-out|ease-in-out|step-start|step-end|cubic-bezier\([-\d., ]{1,60}\)|steps\([a-z0-9, -]{1,40}\))$/;
const FEATURES = /^"[a-zA-Z0-9]{4}" \d{1,3}(?:, ?"[a-zA-Z0-9]{4}" \d{1,3})*$/;
const UNICODE_RANGE =
  /^U\+[0-9A-Fa-f]{1,6}(?:-[0-9A-Fa-f]{1,6})?(?:, ?U\+[0-9A-Fa-f]{1,6}(?:-[0-9A-Fa-f]{1,6})?)*$/;

const BUILT_IN_FAMILIES = ['version', 'color', 'type', 'spacing', 'radius', 'shadow'];
const MAX_ALIAS_DEPTH = 16;
const CAPS = {
  themes: 8,
  colors: 600,
  fonts: 4096,
  families: 12,
  groups: 12,
  styles: 80,
  tokensPerFamily: 60,
  extraFamilies: 12,
  name: 120,
  usage: 1000,
};
const FONT_FORMATS = { woff2: 'woff2', woff: 'woff', ttf: 'truetype', otf: 'opentype' };

const isObject = (v) => typeof v === 'object' && v !== null && !Array.isArray(v);
const list = (v) => (Array.isArray(v) ? v : []);
const has = (o, k) => Object.hasOwn(o, k);

function clamp(text, max) {
  if (typeof text !== 'string') return '';
  const chars = Array.from(text);
  return chars.length > max ? chars.slice(0, max).join('') : text;
}

function aliasOf(value) {
  return value === undefined ? undefined : ALIAS.exec(value)?.[1];
}

function isColor(v) {
  return typeof v === 'string' && (HEX.test(v) || (COLOR_FN.test(v) && !/url|var|expression/.test(v)));
}

function colorValue(v) {
  if (typeof v !== 'string') return undefined;
  const lower = v.trim().toLowerCase();
  if (isColor(lower)) return lower;
  const alias = v.trim().replace(/^\{--/, '{');
  return ALIAS.test(alias) ? alias : undefined;
}

function length(v, isRadius) {
  if (typeof v === 'number' && Number.isFinite(v)) {
    if (Math.abs(v) >= 1e4) return undefined;
    return v === 0 ? '0' : String(Math.round(v * 1000) / 1000) + 'px';
  }
  if (typeof v !== 'string') return undefined;
  const s = v.trim().toLowerCase();
  if (isRadius && s === 'full') return '9999px';
  return LENGTH.test(s) ? s : undefined;
}

function lineHeight(v) {
  if (typeof v === 'number' && Number.isFinite(v) && v > 0 && v < 10) {
    return String(Math.round(v * 1000) / 1000);
  }
  if (typeof v === 'string' && /^\d(?:\.\d{1,6})?$/.test(v.trim())) {
    return Number(v) > 0 ? v.trim() : undefined;
  }
  return length(v, false);
}

function fontWeight(v) {
  if (typeof v === 'number' && Number.isInteger(v) && v >= 1 && v <= 1000) return String(v);
  if (typeof v !== 'string') return undefined;
  const s = v.trim().replace(/\s+/g, ' ');
  if (s === 'normal') return '400';
  if (s === 'bold') return '700';
  return FONT_WEIGHT.test(s) ? s : undefined;
}

function opticalSize(v) {
  if (v === undefined || v === null || v === '' || v === 'auto' || v === 'none') return null;
  const n =
    typeof v === 'number' ? v : typeof v === 'string' && /^\d{1,4}(?:\.\d{1,3})?$/.test(v) ? Number(v) : NaN;
  return Number.isFinite(n) && n >= 1 && n <= 1000 ? n : null;
}

function isFontStack(v) {
  if (!FONT_STACK.test(v) || v.includes('/*') || v.includes('*/')) return false;
  let quote = null;
  for (const ch of v) {
    if (quote === null) {
      if (ch === '"' || ch === "'") quote = ch;
    } else if (ch === quote) quote = null;
  }
  return quote === null;
}

function balancedParens(v) {
  let depth = 0;
  for (const ch of v) {
    if (ch === '(') depth++;
    else if (ch === ')' && --depth < 0) return false;
  }
  return depth === 0;
}

function shadowValue(v) {
  if (typeof v !== 'string') return undefined;
  const s = v.trim().toLowerCase().replace(/\s+/g, ' ');
  if (!SHADOW.test(s) || /(?:url|var|expression|image|image-set|src)\s*\(/.test(s) || s.trim() === '') {
    return undefined;
  }
  return balancedParens(s) ? s : undefined;
}

function plainValue(v) {
  if (typeof v === 'number' && Number.isFinite(v) && Math.abs(v) < 1e6) return String(v);
  if (typeof v !== 'string') return undefined;
  const s = v.trim().replace(/\s+/g, ' ');
  if (!PLAIN_VALUE.test(s) || /(?:url|var|expression|image|image-set|src)\s*\(/i.test(s)) return undefined;
  return balancedParens(s) ? s : undefined;
}

function motionValue(v) {
  if (typeof v === 'number' && Number.isFinite(v) && v >= 0 && v < 1e6) return String(Math.round(v)) + 'ms';
  if (typeof v !== 'string') return undefined;
  let s = v.trim().toLowerCase().replace(/\s+/g, ' ');
  if (s.startsWith('.')) s = '0' + s;
  return DURATION.test(s) || EASING.test(s) ? s : undefined;
}

function isFilePath(p) {
  if (p.length === 0 || p.startsWith('/') || p.includes('\\')) return false;
  return p.split('/').every((seg) => seg !== '' && seg !== '.' && seg !== '..' && !seg.startsWith('.'));
}

// Drops color values whose alias names a missing token, itself, a cycle or a
// chain deeper than MAX_ALIAS_DEPTH; a token left without a first-theme value
// borrows one from a later theme or drops.
function aliasProblem(token, themeId, firstId, byName) {
  const seen = new Set([token.name]);
  let current = token;
  for (let i = 0; i <= MAX_ALIAS_DEPTH; i++) {
    const target = aliasOf(current.value[themeId] ?? current.value[firstId]);
    if (target === undefined) return undefined;
    const next = byName.get(target);
    if (next === undefined) return { own: i === 0 };
    if (seen.has(target)) return { own: target === token.name };
    seen.add(target);
    current = next;
  }
  return { own: true };
}

function dropBrokenAliases(themes, tokens) {
  const firstId = themes[0].id;
  for (;;) {
    const byName = new Map(tokens.map((t) => [t.name, t]));
    const problems = [];
    for (const token of tokens) {
      for (const theme of themes) {
        if (aliasOf(token.value[theme.id]) === undefined) continue;
        const problem = aliasProblem(token, theme.id, firstId, byName);
        if (problem !== undefined) problems.push([token, theme.id, problem.own]);
      }
    }
    if (problems.length === 0) return;
    const anyOwn = problems.some(([, , own]) => own);
    for (const [token, themeId, own] of problems) {
      if (anyOwn && !own) continue;
      delete token.value[themeId];
    }
    for (let i = 0; i < tokens.length; i++) {
      const token = tokens[i];
      if (has(token.value, firstId)) continue;
      const donor = themes.find((t) => has(token.value, t.id));
      if (donor === undefined) tokens.splice(i--, 1);
      else {
        token.value[firstId] = token.value[donor.id];
        delete token.value[donor.id];
      }
    }
  }
}

// Reads tokens.json the way the DS page does: keeps only what the grammar
// accepts and fills the defaults the compiler relies on.
function normalizeTokens(src) {
  if (!isObject(src)) throw new TypeError('tokens.json must be a JSON object');

  const color = isObject(src.color) ? src.color : {};
  const themes = [];
  const themeIds = new Set();
  for (const raw of list(color.themes)) {
    if (themes.length >= CAPS.themes) break;
    const id =
      isObject(raw) && typeof raw.id === 'string'
        ? raw.id.trim().toLowerCase()
        : typeof raw === 'string'
          ? raw.trim().toLowerCase()
          : '';
    if (!NAME.test(id) || themeIds.has(id) || has(Object.prototype, id)) continue;
    themeIds.add(id);
    themes.push({ id });
  }
  if (themes.length === 0) themes.push({ id: 'light' });
  const firstId = themes[0].id;

  const colorTokens = [];
  const colorNames = new Set();
  const rawColors = Array.isArray(color.tokens)
    ? color.tokens
    : list(color.groups).flatMap((g) => (isObject(g) ? list(g.tokens) : []));
  for (const raw of rawColors) {
    if (colorTokens.length >= CAPS.colors) break;
    if (!isObject(raw)) continue;
    const name = typeof raw.name === 'string' ? raw.name.trim().replace(/^--/, '') : '';
    if (!NAME.test(name) || colorNames.has(name)) continue;
    const value = {};
    if (isObject(raw.value)) {
      for (const theme of themes) {
        const v = has(raw.value, theme.id) ? colorValue(raw.value[theme.id]) : undefined;
        if (v !== undefined) value[theme.id] = v;
      }
    } else {
      const v = colorValue(raw.value);
      if (v !== undefined) value[firstId] = v;
    }
    if (!has(value, firstId)) {
      const borrowed = themes.map((t) => value[t.id]).find((v) => v !== undefined);
      if (borrowed === undefined) continue;
      value[firstId] = borrowed;
    }
    colorNames.add(name);
    colorTokens.push({ name, value, usage: clamp(raw.usage, CAPS.usage) });
  }
  dropBrokenAliases(themes, colorTokens);

  const type = isObject(src.type) ? src.type : {};
  const fonts = [];
  for (const raw of list(type.fonts)) {
    if (fonts.length >= CAPS.fonts) break;
    if (!isObject(raw)) continue;
    const family = clamp(raw.family, CAPS.name).trim();
    const file = typeof raw.file === 'string' ? raw.file.trim() : '';
    if (family === '' || !isFontStack(family) || /["']/.test(family)) continue;
    const path = file.includes('/') ? file : 'fonts/' + file;
    if (file === '' || !isFilePath(path) || /^[a-z][a-z0-9+.-]*:/i.test(file)) continue;
    const features = typeof raw.features === 'string' && FEATURES.test(raw.features) ? raw.features : undefined;
    const unicodeRange =
      typeof raw.unicodeRange === 'string' && UNICODE_RANGE.test(raw.unicodeRange) ? raw.unicodeRange : undefined;
    fonts.push({
      family,
      file: path,
      weight: fontWeight(raw.weight) ?? '400',
      style: raw.style === 'italic' || raw.style === 'oblique' ? raw.style : 'normal',
      ...(features !== undefined && { features }),
      ...(unicodeRange !== undefined && { unicodeRange }),
    });
  }

  const families = {};
  if (isObject(type.families)) {
    let count = 0;
    for (const key of Object.keys(type.families)) {
      if (count >= CAPS.families) break;
      const id = key.trim().toLowerCase();
      const stack = type.families[key];
      if (!NAME.test(id) || typeof stack !== 'string' || !isFontStack(stack.trim())) continue;
      families[id] = stack.trim();
      count++;
    }
  }
  const familyKey = (v) =>
    typeof v === 'string' && has(families, v.trim().toLowerCase()) ? v.trim().toLowerCase() : '';

  const groups = [];
  const styleNames = new Set();
  let styleCount = 0;
  for (const raw of list(type.groups)) {
    if (groups.length >= CAPS.groups) break;
    if (!isObject(raw)) continue;
    const styles = [];
    for (const s of list(raw.styles)) {
      if (styleCount >= CAPS.styles) break;
      if (!isObject(s)) continue;
      const name = typeof s.name === 'string' ? s.name.trim().replace(/^\./, '') : '';
      if (!NAME.test(name) || styleNames.has(name)) continue;
      const fontSize = length(s.fontSize, false);
      if (fontSize === undefined) continue;
      styles.push({
        name,
        fontSize,
        lineHeight: lineHeight(s.lineHeight) ?? '1.4',
        fontWeight: fontWeight(s.fontWeight) ?? '400',
        letterSpacing: length(s.letterSpacing, false) ?? '0',
        fontStyle: s.fontStyle === 'italic' ? 'italic' : 'normal',
        opticalSize: opticalSize(s.opticalSize),
        family: familyKey(s.family),
        usage: clamp(s.usage, CAPS.usage),
      });
      styleNames.add(name);
      styleCount++;
    }
    groups.push({ family: familyKey(raw.family), styles });
  }

  // Every family but type shares one --name namespace; colors claim theirs first.
  const owner = new Map(colorTokens.map((t) => [t.name, 'color']));
  const family = (raw, key, read) => {
    const tokens = [];
    for (const t of list(isObject(raw) ? raw.tokens : undefined)) {
      if (tokens.length >= CAPS.tokensPerFamily) break;
      if (!isObject(t)) continue;
      const name = typeof t.name === 'string' ? t.name.trim().replace(/^--/, '') : '';
      if (!NAME.test(name) || owner.has(name)) continue;
      const value = read(t.value);
      if (value === undefined) continue;
      owner.set(name, key);
      tokens.push({ name, value, usage: clamp(t.usage, CAPS.usage) });
    }
    return tokens;
  };
  const themedShadow = (raw) => {
    const value = {};
    if (isObject(raw)) {
      for (const theme of themes) {
        if (!has(raw, theme.id)) continue;
        const v = shadowValue(raw[theme.id]);
        if (v !== undefined) value[theme.id] = v;
      }
    } else {
      const v = shadowValue(raw);
      if (v !== undefined) value[firstId] = v;
    }
    if (!has(value, firstId)) {
      const donor = themes.find((t) => has(value, t.id));
      if (donor === undefined) return undefined;
      value[firstId] = value[donor.id];
      delete value[donor.id];
    }
    return value;
  };

  const spacing = family(src.spacing, 'spacing', (v) => length(v, false));
  const radius = family(src.radius, 'radius', (v) => length(v, true));
  const shadow = family(src.shadow, 'shadow', themedShadow);
  const extraFamilies = [];
  for (const key of Object.keys(src)) {
    if (BUILT_IN_FAMILIES.includes(key) || key === 'name') continue;
    const raw = src[key];
    if (!FAMILY_KEY.test(key) || !isObject(raw) || !Array.isArray(raw.tokens)) continue;
    if (extraFamilies.length >= CAPS.extraFamilies) continue;
    const read = key === 'motion' ? (v) => motionValue(v) ?? plainValue(v) : plainValue;
    extraFamilies.push(family(raw, key, read));
  }
  const extra = extraFamilies.flat();

  return { themes, colors: colorTokens, shadow, spacing, radius, extra, families, groups, fonts };
}

function cssName(name) {
  let escaped = name.replace(/\./g, '\\.');
  if (/^[0-9]/.test(escaped)) escaped = '\\3' + escaped[0] + ' ' + escaped.slice(1);
  return escaped;
}

// Text placed inside a CSS comment must not end it (`*/`) or a <style> (`</`).
function closeProof(text) {
  return text.replace(/\*\//g, '* /').replace(/<\//g, '< /');
}

function comment(text) {
  return closeProof(text).replace(/[\r\n\f]+/g, ' ').trim();
}

function fontUrl(path) {
  return path.split('/').map(encodeURIComponent).join('/');
}

/**
 * tokens.json (object or JSON text) → tokens.css text, byte for byte what the
 * DS page compiles.
 * @param {object|string} input
 * @param {{name?: string}} [options] name for the header line; defaults to tokens.json `name`.
 */
export function compileTokensCss(input, options = {}) {
  const src = typeof input === 'string' ? JSON.parse(input) : input;
  const t = normalizeTokens(src);
  const rawName = options.name ?? (typeof src?.name === 'string' ? src.name : '');
  const title =
    rawName.trim() !== ''
      ? closeProof(Array.from(rawName.replace(/\s+/g, ' ').trim()).slice(0, 200).join(''))
      : 'Design system';

  const out = [];
  const usage = (u) => (u !== '' ? ' /* ' + comment(u) + ' */' : '');
  const firstId = t.themes[0].id;
  const colorDecl = (token, value, withUsage) => {
    const target = aliasOf(value);
    return (
      '  --' +
      cssName(token.name) +
      ': ' +
      (target !== undefined ? 'var(--' + cssName(target) + ')' : value) +
      ';' +
      (withUsage ? usage(token.usage) : '')
    );
  };
  const decl = (token, value, withUsage) =>
    '  --' + cssName(token.name) + ': ' + value + ';' + (withUsage ? usage(token.usage) : '');

  if (t.colors.length > 0 || t.shadow.length > 0) {
    out.push(':root, [data-theme="' + firstId + '"] {');
    for (const c of t.colors) out.push(colorDecl(c, c.value[firstId], true));
    for (const s of t.shadow) out.push(decl(s, s.value[firstId], true));
    out.push('}');
    for (const theme of t.themes.slice(1)) {
      const lines = [
        ...t.colors
          .filter((c) => c.value[theme.id] !== undefined || aliasOf(c.value[firstId]) !== undefined)
          .map((c) => colorDecl(c, c.value[theme.id] ?? c.value[firstId], false)),
        ...t.shadow.filter((s) => s.value[theme.id] !== undefined).map((s) => decl(s, s.value[theme.id], false)),
      ];
      if (lines.length > 0) out.push('[data-theme="' + theme.id + '"] {', ...lines, '}');
    }
  }

  const root = [];
  for (const token of [...t.spacing, ...t.radius, ...t.extra]) root.push(decl(token, token.value, true));
  for (const [key, stack] of Object.entries(t.families)) root.push('  --font-' + cssName(key) + ': ' + stack + ';');
  // Shorthand per text style (`--text-<style>`), skipped where a token already owns that name.
  const tokenNames = new Set(
    [...t.colors, ...t.spacing, ...t.radius, ...t.shadow, ...t.extra].map((x) => cssName(x.name)),
  );
  for (const group of t.groups) {
    for (const style of group.styles) {
      const varName = 'text-' + cssName(style.name);
      if (tokenNames.has(varName)) continue;
      const fam = style.family !== '' ? style.family : group.family;
      root.push(
        '  --' +
          varName +
          ': ' +
          (style.fontStyle !== 'normal' ? style.fontStyle + ' ' : '') +
          style.fontWeight +
          ' ' +
          style.fontSize +
          '/' +
          style.lineHeight +
          (fam !== '' ? ' var(--font-' + cssName(fam) + ')' : ' inherit') +
          ';' +
          usage(style.usage),
      );
    }
  }
  if (root.length > 0) out.push(':root {', ...root, '}');

  for (const group of t.groups) {
    for (const style of group.styles) {
      const fam = style.family !== '' ? style.family : group.family;
      out.push(
        '.' + cssName(style.name) + ' {',
        ...(fam !== '' ? ['  font-family: var(--font-' + cssName(fam) + ');'] : []),
        '  font-size: ' + style.fontSize + ';',
        '  line-height: ' + style.lineHeight + ';',
        '  font-weight: ' + style.fontWeight + ';',
        '  letter-spacing: ' + style.letterSpacing + ';',
        ...(style.fontStyle !== 'normal' ? ['  font-style: ' + style.fontStyle + ';'] : []),
        ...(style.opticalSize !== null ? ["  font-variation-settings: 'opsz' " + String(style.opticalSize) + ';'] : []),
        '}',
      );
    }
  }

  for (const font of t.fonts) {
    const ext = font.file.slice(font.file.lastIndexOf('.') + 1).toLowerCase();
    const format = FONT_FORMATS[ext];
    out.push(
      '@font-face {',
      '  font-family: "' + font.family + '";',
      '  src: url("' + fontUrl(font.file).replace(/["\\\n\r\f]/g, '') + '")' + (format ? ' format("' + format + '")' : '') + ';',
      '  font-weight: ' + font.weight + ';',
      '  font-style: ' + font.style + ';',
      '  font-display: swap;',
      ...(font.features !== undefined ? ['  font-feature-settings: ' + font.features + ';'] : []),
      ...(font.unicodeRange !== undefined ? ['  unicode-range: ' + font.unicodeRange + ';'] : []),
      '}',
    );
  }

  return '/* ' + title + ' — generated from tokens.json */\n' + out.join('\n') + (out.length > 0 ? '\n' : '');
}
