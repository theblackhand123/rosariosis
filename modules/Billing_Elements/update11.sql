/**
 * Update SQL from version 2+ to version 11.0
 *
 * Same file for MySQL & PostgreSQL
 */


/**
 * Add grade_levels column to billing_elements table.
 *
 * @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
 */
ALTER TABLE billing_elements
	ADD COLUMN grade_levels text;

/**
 * Move GRADE_LEVEL column values to GRADE_LEVELS
 *
 * @deprecated SQL GRADE_LEVEL column, since 11.0 use GRADE_LEVELS instead
 */
UPDATE billing_elements
SET GRADE_LEVELS=CONCAT(',', GRADE_LEVEL, ',')
WHERE GRADE_LEVEL IS NOT NULL;
