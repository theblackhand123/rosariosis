/**
 * Install PostgreSQL
 * Required as the module adds programs to other modules
 * - Add profile exceptions for the module to appear in the menu
 * - Add module specific tables (and their eventual sequences & indexes)
 *
 * @package School Inventory module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION create_language_plpgsql()
RETURNS BOOLEAN AS $$
    CREATE LANGUAGE plpgsql;
    SELECT TRUE;
$$ LANGUAGE SQL;

SELECT CASE WHEN NOT (
    SELECT TRUE AS exists FROM pg_language
    WHERE lanname='plpgsql'
    UNION
    SELECT FALSE AS exists
    ORDER BY exists DESC
    LIMIT 1
) THEN
    create_language_plpgsql()
ELSE
    FALSE
END AS plpgsql_created;

DROP FUNCTION create_language_plpgsql();



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
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/SchoolInventory.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'School_Inventory/SchoolInventory.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/SchoolInventory.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'School_Inventory/InventorySnapshots.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='School_Inventory/InventorySnapshots.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'School_Inventory/InventorySnapshots.php', 'Y', null
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

CREATE OR REPLACE FUNCTION create_table_school_inventory_categoryxitem() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'school_inventory_categoryxitem') THEN
    RAISE NOTICE 'Table "school_inventory_categoryxitem" already exists.';
    ELSE
        CREATE TABLE school_inventory_categoryxitem (
            item_id integer NOT NULL,
            category_id integer NOT NULL,
            category_type varchar(255) NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_categoryxitem();
DROP FUNCTION create_table_school_inventory_categoryxitem();



--
-- Name: school_inventory_categoryxitem_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_categoryxitem_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_categoryxitem_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX school_inventory_categoryxitem_ind ON school_inventory_categoryxitem (category_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_categoryxitem_ind();
DROP FUNCTION create_index_school_inventory_categoryxitem_ind();



/**
 * Items table
 */
--
-- Name: items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_items() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'school_inventory_items') THEN
    RAISE NOTICE 'Table "school_inventory_items" already exists.';
    ELSE
        CREATE TABLE school_inventory_items (
            item_id serial PRIMARY KEY,
            school_id integer NOT NULL,
            title text NOT NULL,
            sort_order numeric,
            type varchar(255),
            quantity numeric(11,2),
            comments text,
            file text,
            price numeric(14,2),
            "date" date,
            created_at timestamp DEFAULT current_timestamp,
            updated_at timestamp
        );

        CREATE TRIGGER set_updated_at
            BEFORE UPDATE ON school_inventory_items
            FOR EACH ROW EXECUTE PROCEDURE set_updated_at();
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_items();
DROP FUNCTION create_table_school_inventory_items();



--
-- Name: school_inventory_items_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_items_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_items_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX school_inventory_items_ind ON school_inventory_items (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_items_ind();
DROP FUNCTION create_index_school_inventory_items_ind();



/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_categories() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'school_inventory_categories') THEN
    RAISE NOTICE 'Table "school_inventory_categories" already exists.';
    ELSE
        CREATE TABLE school_inventory_categories (
            category_id serial PRIMARY KEY,
            category_type varchar(255) NOT NULL,
            category_key varchar(255),
            school_id integer NOT NULL,
            title varchar(255) NOT NULL,
            sort_order numeric,
            color varchar(255)
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_categories();
DROP FUNCTION create_table_school_inventory_categories();



--
-- Name: school_inventory_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_categories_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_categories_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX school_inventory_categories_ind ON school_inventory_categories (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_categories_ind();
DROP FUNCTION create_index_school_inventory_categories_ind();



/**
 * Snapshots table
 */
--
-- Name: school_inventory_snapshots; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_snapshots() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'school_inventory_snapshots') THEN
    RAISE NOTICE 'Table "school_inventory_snapshots" already exists.';
    ELSE
        CREATE TABLE school_inventory_snapshots (
            id serial PRIMARY KEY,
            school_id integer NOT NULL,
            title varchar(255) NOT NULL,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;
SELECT create_table_school_inventory_snapshots();
DROP FUNCTION create_table_school_inventory_snapshots();



/**
 * Snapshot Category cross item table
 */
--
-- Name: school_inventory_snapshot_categoryxitem; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_snapshot_categoryxitem() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'school_inventory_snapshot_categoryxitem') THEN
    RAISE NOTICE 'Table "school_inventory_snapshot_categoryxitem" already exists.';
    ELSE
        CREATE TABLE school_inventory_snapshot_categoryxitem (
            item_id integer NOT NULL,
            category_id integer NOT NULL,
            category_type varchar(255) NOT NULL,
            snapshot_id integer NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_snapshot_categoryxitem();
DROP FUNCTION create_table_school_inventory_snapshot_categoryxitem();



--
-- Name: school_inventory_snapshot_categoryxitem_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_snapshot_categoryxitem_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_snapshot_categoryxitem_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX school_inventory_snapshot_categoryxitem_ind ON school_inventory_snapshot_categoryxitem (category_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_snapshot_categoryxitem_ind();
DROP FUNCTION create_index_school_inventory_snapshot_categoryxitem_ind();



/**
 * Snapshot Items table
 */
--
-- Name: school_inventory_snapshot_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_snapshot_items() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'school_inventory_snapshot_items') THEN
    RAISE NOTICE 'Table "school_inventory_snapshot_items" already exists.';
    ELSE
        CREATE TABLE school_inventory_snapshot_items (
            item_id integer NOT NULL,
            school_id integer NOT NULL,
            title text NOT NULL,
            sort_order numeric,
            type varchar(255),
            quantity numeric(11,2),
            comments text,
            file text,
            price numeric(14,2),
            "date" date,
            snapshot_id integer NOT NULL
       );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_snapshot_items();
DROP FUNCTION create_table_school_inventory_snapshot_items();



--
-- Name: school_inventory_snapshot_items_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_snapshot_items_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_snapshot_items_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX school_inventory_snapshot_items_ind ON school_inventory_snapshot_items (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_snapshot_items_ind();
DROP FUNCTION create_index_school_inventory_snapshot_items_ind();



/**
 * Snapshot Categories table
 */
--
-- Name: school_inventory_snapshot_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_school_inventory_snapshot_categories() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'school_inventory_snapshot_categories') THEN
    RAISE NOTICE 'Table "school_inventory_snapshot_categories" already exists.';
    ELSE
        CREATE TABLE school_inventory_snapshot_categories (
            category_id integer NOT NULL,
            category_type varchar(255) NOT NULL,
            category_key varchar(255),
            school_id integer NOT NULL,
            title varchar(255) NOT NULL,
            sort_order numeric,
            color varchar(255),
            snapshot_id integer NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_school_inventory_snapshot_categories();
DROP FUNCTION create_table_school_inventory_snapshot_categories();



--
-- Name: school_inventory_snapshot_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_school_inventory_snapshot_categories_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='school_inventory_snapshot_categories_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX school_inventory_snapshot_categories_ind ON school_inventory_snapshot_categories (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_school_inventory_snapshot_categories_ind();
DROP FUNCTION create_index_school_inventory_snapshot_categories_ind();



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
