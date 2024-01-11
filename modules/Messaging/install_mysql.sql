/**
 * Install MySQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Messaging module
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
SELECT 1, 'Messaging/Messages.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Messaging/Write.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Messaging/Messages.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Messaging/Write.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Messaging/Messages.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Messaging/Write.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Messaging/Messages.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Messages.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Messaging/Write.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Messaging/Write.php'
    AND profile_id=0);



/**
 * Add module tables
 */

/**
 * User cross message table
 */
--
-- Name: messagexuser; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS messagexuser (
    user_id integer NOT NULL,
    `key` varchar(10),
    message_id integer NOT NULL,
    status varchar(10) NOT NULL
);


--
-- Name: messagexuser_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_messagexuser_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='messagexuser'
    AND index_name='messagexuser_ind';

    IF index_exists=0 THEN
        CREATE INDEX messagexuser_ind ON messagexuser (user_id, `key`, status);
    END IF;
END $$
DELIMITER ;

CALL create_messagexuser_ind();
DROP PROCEDURE create_messagexuser_ind;


/**
 * Messages table
 */
--
-- Name: messages; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS messages (
    message_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    `from` varchar(255),
    recipients longtext,
    subject varchar(100),
    `datetime` timestamp,
    data longtext,
    created_at timestamp DEFAULT current_timestamp,
    FOREIGN KEY (school_id, syear) REFERENCES schools(id, syear)
);
