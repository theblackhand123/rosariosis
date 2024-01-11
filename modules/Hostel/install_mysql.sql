/**
 * Install MySQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Hostel module
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
SELECT 1, 'Hostel/Hostel.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/Hostel.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Hostel/Hostel.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/Hostel.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Hostel/Hostel.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/Hostel.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Hostel/RoomList.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Hostel/RoomList.php'
    AND profile_id=1);


/**
 * Add module tables
 */

/**
 * Buildings table
 */
--
-- Name: hostel_buildings; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS hostel_buildings (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- school_id integer NOT NULL,
    title text NOT NULL,
    description text,
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp
);


/**
 * Hostel Rooms table
 */
--
-- Name: hostel_rooms; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS hostel_rooms (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- school_id integer NOT NULL,
    building_id integer NOT NULL,
    FOREIGN KEY (building_id) REFERENCES hostel_buildings(id),
    title text NOT NULL,
    description text,
    capacity integer NOT NULL,
    price numeric(14,2),
    created_at timestamp DEFAULT current_timestamp
);


/**
 * Hostel Students table
 */
--
-- Name: hostel_students; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS hostel_students (
    room_id integer NOT NULL,
    FOREIGN KEY (room_id) REFERENCES hostel_rooms(id),
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    created_at timestamp DEFAULT current_timestamp,
    UNIQUE (room_id, student_id)
);
