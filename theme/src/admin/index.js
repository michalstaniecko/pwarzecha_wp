/**
 * Admin screens script (`build/admin/`, ADR 0013). Enqueued by `PW\Data\Admin\enqueue_admin_scripts()`
 * on the Lokalizacja edit screen (image fields and the Grafik rows).
 */
import { initMediaFields } from './media-field.js';
import { initGrafikRows } from './grafik-rows.js';

initMediaFields();
initGrafikRows();
