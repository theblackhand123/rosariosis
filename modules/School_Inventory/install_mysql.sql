/**
 * Install MySQL
 * Required as the module adds programs to other modules
 * - Add profile exceptions for the module to appear in the menu
 * - Add module specific tables (and their eventual sequences & indexes)
 *
 * @package School Inventory module
 */

/*******************************************************
 profile_id:
 	- 0: student
 	- 1: admin
 	- 2: teacher
 	- 3: parent
 modname: should match the Menu.php entries
 can_use: 'Y'
 can_edit: 'Y' or null (generally null for non admins)
*******************************************************/
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'School_Inventory/SchoolInventory.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/SchoolInventory.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'School_Inventory/SchoolInventory.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/SchoolInventory.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'School_Inventory/InventorySnapshots.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/InventorySnapshots.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'School_Inventory/InventorySnapshots.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/InventorySnapshots.php'
    AND profile_id=2);

/**
 * Add module tables
 */

/**
 * Category cross item table
 */
--
-- Name: school_inventory_categoryxitem; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS school_inventory_categoryxitem (
    item_id integer NOT NULL,
    category_id integer NOT NULL,
    category_type varchar(255) NOT NULL
);


--
-- Name: school_inventory_categoryxitem_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_school_inventory_categoryxitem_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='school_inventory_categoryxitem'
    AND index_name='school_inventory_categoryxitem_ind';

    IF index_exists=0 THEN
        CREATE INDEX school_inventory_categoryxitem_ind ON school_inventory_categoryxitem (category_id);
    END IF;
END $$
DELIMITER ;

CALL create_school_inventory_categoryxitem_ind();
DROP PROCEDURE create_school_inventory_categoryxitem_ind;


/**
 * Items table
 */
--
-- Name: items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS school_inventory_items (
    item_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    type varchar(255),
    quantity numeric(11,2),
    comments text,
    file text,
    price numeric(14,2),
    `date` date,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: school_inventory_items_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_school_inventory_items_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='school_inventory_items'
    AND index_name='school_inventory_items_ind';

    IF index_exists=0 THEN
        CREATE INDEX school_inventory_items_ind ON school_inventory_items (school_id);
    END IF;
END $$
DELIMITER ;

CALL create_school_inventory_items_ind();
DROP PROCEDURE create_school_inventory_items_ind;


/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS school_inventory_categories (
    category_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_type varchar(255) NOT NULL,
    category_key varchar(255),
    school_id integer NOT NULL,
    title varchar(255) NOT NULL,
    sort_order numeric,
    color varchar(255)
);


--
-- Name: school_inventory_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_school_inventory_categories_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='school_inventory_categories'
    AND index_name='school_inventory_categories_ind';

    IF index_exists=0 THEN
        CREATE INDEX school_inventory_categories_ind ON school_inventory_categories (school_id);
    END IF;
END $$
DELIMITER ;

CALL create_school_inventory_categories_ind();
DROP PROCEDURE create_school_inventory_categories_ind;


/**
 * Snapshots table
 */
--
-- Name: school_inventory_snapshots; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS school_inventory_snapshots (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title varchar(255) NOT NULL,
    created_at timestamp DEFAULT current_timestamp
);


/**
 * Snapshot Category cross item table
 */
--
-- Name: school_inventory_snapshot_categoryxitem; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS school_inventory_snapshot_categoryxitem (
    item_id integer NOT NULL,
    category_id integer NOT NULL,
    category_type varchar(255) NOT NULL,
    snapshot_id integer NOT NULL
);


--
-- Name: school_inventory_snapshot_categoryxitem_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_school_inventory_snapshot_categoryxitem_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='school_inventory_snapshot_categoryxitem'
    AND index_name='school_inventory_snapshot_categoryxitem_ind';

    IF index_exists=0 THEN
        CREATE INDEX school_inventory_snapshot_categoryxitem_ind ON school_inventory_snapshot_categoryxitem (category_id);
    END IF;
END $$
DELIMITER ;

CALL create_school_inventory_snapshot_categoryxitem_ind();
DROP PROCEDURE create_school_inventory_snapshot_categoryxitem_ind;


/**
 * Snapshot Items table
 */
--
-- Name: school_inventory_snapshot_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS school_inventory_snapshot_items (
    item_id integer NOT NULL,
    school_id integer NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    type varchar(255),
    quantity numeric(11,2),
    comments text,
    file text,
    price numeric(14,2),
    `date` date,
    snapshot_id integer NOT NULL
);


--
-- Name: school_inventory_snapshot_items_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_school_inventory_snapshot_items_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='school_inventory_snapshot_items'
    AND index_name='school_inventory_snapshot_items_ind';

    IF index_exists=0 THEN
        CREATE INDEX school_inventory_snapshot_items_ind ON school_inventory_snapshot_items (school_id);
    END IF;
END $$
DELIMITER ;

CALL create_school_inventory_snapshot_items_ind();
DROP PROCEDURE create_school_inventory_snapshot_items_ind;


/**
 * Snapshot Categories table
 */
--
-- Name: school_inventory_snapshot_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS school_inventory_snapshot_categories (
    category_id integer NOT NULL,
    category_type varchar(255) NOT NULL,
    category_key varchar(255),
    school_id integer NOT NULL,
    title varchar(255) NOT NULL,
    sort_order numeric,
    color varchar(255),
    snapshot_id integer NOT NULL
);


--
-- Name: school_inventory_snapshot_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_school_inventory_snapshot_categories_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='school_inventory_snapshot_categories'
    AND index_name='school_inventory_snapshot_categories_ind';

    IF index_exists=0 THEN
        CREATE INDEX school_inventory_snapshot_categories_ind ON school_inventory_snapshot_categories (school_id);
    END IF;
END $$
DELIMITER ;

CALL create_school_inventory_snapshot_categories_ind();
DROP PROCEDURE create_school_inventory_snapshot_categories_ind;


--
-- Data for Name: school_inventory_categories; Type: TABLE DATA;
--

INSERT INTO school_inventory_categories (school_id, title, sort_order, category_type)
SELECT sch.id, 'Computers', null, 'CATEGORY'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Computers'
    AND category_type='CATEGORY')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (school_id, title, sort_order, category_type)
SELECT sch.id, 'Consumables', null, 'CATEGORY'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Consumables'
    AND category_type='CATEGORY')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (school_id, title, sort_order, category_type)
SELECT sch.id, 'Needs repair', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Needs repair'
    AND category_type='STATUS')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (school_id, title, sort_order, category_type)
SELECT sch.id, 'Buy more', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Buy more'
    AND category_type='STATUS')
GROUP BY sch.id;


INSERT INTO school_inventory_categories (school_id, title, sort_order, category_type)
SELECT sch.id, 'Lent', null, 'STATUS'
FROM schools sch
WHERE NOT EXISTS (SELECT title
    FROM school_inventory_categories
    WHERE title='Lent'
    AND category_type='STATUS')
GROUP BY sch.id;
