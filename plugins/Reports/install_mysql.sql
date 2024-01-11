/**
 * Install MySQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Reports module
 */

--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Reports/SavedReports.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Reports/SavedReports.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Reports/Calculations.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Reports/Calculations.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Reports/CalculationsReports.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Reports/CalculationsReports.php'
    AND profile_id=1);


--
-- Name: saved_calculations; Type: TABLE; ; Tablespace:
--

CREATE TABLE IF NOT EXISTS saved_calculations (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title varchar(100),
    url varchar(5000),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: saved_reports; Type: TABLE; ; Tablespace:
--

CREATE TABLE IF NOT EXISTS saved_reports (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title varchar(100),
    staff_id integer,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    php_self varchar(5000),
    search_php_self varchar(5000),
    search_vars varchar(5000),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);
