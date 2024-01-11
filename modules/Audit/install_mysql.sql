/**
 * Install MySQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Audit module
 */

/**
 * profile_exceptions Table
 *
 * profile_id:
 * - 0: student
 * - 1: admin
 * - 2: teacher
 * - 3: parent
 * modname: should match the Menu.php entries
 * can_use: 'Y'
 * can_edit: 'Y' or null (generally null for non admins)
 */
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Audit/AuditLog.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Audit/AuditLog.php'
    AND profile_id=1);


/**
 * Add module tables
 */

/**
 * Audit Log table
 */
--
-- Name: sql_audit_log; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS audit_log (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0),
    username varchar(100),
    profile varchar(30),
    url varchar(2000),
    query_type varchar(50),
    data longtext,
    created_at timestamp DEFAULT current_timestamp
);
