/**
 * Update SQL from version 1.0 to version 2.x
 */


INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Billing_Elements/Elements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Billing_Elements/Elements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/Elements.php'
    AND profile_id=3);


INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Billing_Elements/StudentElements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Billing_Elements/StudentElements.php', 'Y', NULL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Billing_Elements/StudentElements.php'
    AND profile_id=3);


/**
 * Add course_period_id column to billing_elements table.
 */
ALTER TABLE ONLY billing_elements
			ADD COLUMN course_period_id integer;
