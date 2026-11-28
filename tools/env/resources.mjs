/**
 * Prefix of every Docker resource (container, volume, network, Compose project)
 * created for tests or one-off checks (ADR 0015). The dev instance never uses it.
 */
export const TEST_PREFIX = 'pw-test';

/**
 * Names from a Docker listing that are test leftovers and safe to remove.
 *
 * @param {string[]} names Container, volume or network names.
 * @return {string[]} The subset starting with TEST_PREFIX.
 */
export function testResources( names ) {
	return names.filter( ( name ) => name.startsWith( TEST_PREFIX ) );
}
