/**
 * Update 2.0 SQL
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


/**
 * Add module tables
 */

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
