/**
 * Install SQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Quiz module
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
SELECT 1, 'Quiz/Quizzes.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Quizzes.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Quiz/Quizzes.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Quizzes.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Quiz/Questions.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Questions.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Quiz/Questions.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Questions.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Quiz/StudentQuizzes.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/StudentQuizzes.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Quiz/StudentQuizzes.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/StudentQuizzes.php'
    AND profile_id=3);


/**
 * Add module tables
 */

/**
 * Quiz table
 */
--
-- Name: quiz; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_quiz() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'quiz') THEN
    RAISE NOTICE 'Table "quiz" already exists.';
    ELSE
        CREATE TABLE quiz (
            id serial PRIMARY KEY,
            school_id integer NOT NULL,
            staff_id integer NOT NULL REFERENCES staff(staff_id),
            assignment_id integer NOT NULL,
            title text NOT NULL,
            description text,
            options text,
            -- shuffle varchar(10),
            -- show_correct_answers varchar(10),
            -- allow_edit varchar(10),
            -- file text,
            created_at timestamp DEFAULT current_timestamp,
            created_by integer NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_quiz();
DROP FUNCTION create_table_quiz();



--
-- Name: quiz_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_quiz_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='quiz_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX quiz_ind ON quiz (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_quiz_ind();
DROP FUNCTION create_index_quiz_ind();


/**
 * Quiz cross question table
 */
--
-- Name: quiz_quizxquestion; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_quiz_quizxquestion() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'quiz_quizxquestion') THEN
    RAISE NOTICE 'Table "quiz_quizxquestion" already exists.';
    ELSE
        CREATE TABLE quiz_quizxquestion (
            id serial PRIMARY KEY,
            quiz_id integer NOT NULL,
            question_id integer NOT NULL,
            points numeric(4,0) NOT NULL,
            sort_order numeric
       );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_quiz_quizxquestion();
DROP FUNCTION create_table_quiz_quizxquestion();



--
-- Name: quiz_quizxquestion_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_quiz_quizxquestion_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='quiz_quizxquestion_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE UNIQUE INDEX quiz_quizxquestion_ind ON quiz_quizxquestion (quiz_id, question_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_quiz_quizxquestion_ind();
DROP FUNCTION create_index_quiz_quizxquestion_ind();


/**
 * Quiz answers table
 */
--
-- Name: quiz_answers; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_quiz_answers() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'quiz_answers') THEN
    RAISE NOTICE 'Table "quiz_answers" already exists.';
    ELSE
        CREATE TABLE quiz_answers (
            id serial PRIMARY KEY,
            quizxquestion_id integer NOT NULL,
            student_id integer NOT NULL REFERENCES students(student_id),
            answer text,
            points numeric(4,0),
            created_at timestamp DEFAULT current_timestamp,
            modified_at timestamp
       );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_quiz_answers();
DROP FUNCTION create_table_quiz_answers();



--
-- Name: quiz_answers_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_quiz_answers_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='quiz_answers_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE UNIQUE INDEX quiz_answers_ind ON quiz_answers (quizxquestion_id, student_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_quiz_answers_ind();
DROP FUNCTION create_index_quiz_answers_ind();


/**
 * Questions table
 */
--
-- Name: questions; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_quiz_questions() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'quiz_questions') THEN
    RAISE NOTICE 'Table "quiz_questions" already exists.';
    ELSE
        CREATE TABLE quiz_questions (
            id serial PRIMARY KEY,
            school_id integer NOT NULL,
            title text NOT NULL,
            type varchar(10),
            category_id integer NOT NULL,
            description text,
            sort_order numeric,
            answer text,
            file text,
            created_at timestamp DEFAULT current_timestamp,
            created_by integer NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_quiz_questions();
DROP FUNCTION create_table_quiz_questions();


--
-- Name: quiz_questions_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_quiz_questions_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='quiz_questions_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX quiz_questions_ind ON quiz_questions (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_quiz_questions_ind();
DROP FUNCTION create_index_quiz_questions_ind();


/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_quiz_categories() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'quiz_categories') THEN
    RAISE NOTICE 'Table "quiz_categories" already exists.';
    ELSE
        CREATE TABLE quiz_categories (
            id serial PRIMARY KEY,
            school_id integer NOT NULL,
            title text NOT NULL,
            sort_order numeric,
            color varchar(255)
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_quiz_categories();
DROP FUNCTION create_table_quiz_categories();


--
-- Name: quiz_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_quiz_categories_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='quiz_categories_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX quiz_categories_ind ON quiz_categories (school_id);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_quiz_categories_ind();
DROP FUNCTION create_index_quiz_categories_ind();
