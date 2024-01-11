/**
 * Install MySQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Quiz module
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
SELECT 1, 'Quiz/Quizzes.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Quizzes.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Quiz/Quizzes.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Quizzes.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Quiz/Questions.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Questions.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Quiz/Questions.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/Questions.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Quiz/StudentQuizzes.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Quiz/StudentQuizzes.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Quiz/StudentQuizzes.php', 'Y', null
FROM DUAL
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

CREATE TABLE IF NOT EXISTS quiz (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    assignment_id integer NOT NULL,
    title text NOT NULL,
    description longtext,
    options text,
    -- shuffle varchar(10),
    -- show_correct_answers varchar(10),
    -- allow_edit varchar(10),
    -- file text,
    created_at timestamp DEFAULT current_timestamp,
    created_by integer NOT NULL
);


--
-- Name: quiz_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_quiz_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='quiz'
    AND index_name='quiz_ind';

    IF index_exists=0 THEN
        CREATE INDEX quiz_ind ON quiz (school_id);
    END IF;
END $$
DELIMITER ;

CALL create_quiz_ind();
DROP PROCEDURE create_quiz_ind;


/**
 * Quiz cross question table
 */
--
-- Name: quiz_quizxquestion; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS quiz_quizxquestion (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    quiz_id integer NOT NULL,
    question_id integer NOT NULL,
    points numeric(4,0) NOT NULL,
    sort_order numeric
);


--
-- Name: quiz_quizxquestion_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_quiz_quizxquestion_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='quiz_quizxquestion'
    AND index_name='quiz_quizxquestion_ind';

    IF index_exists=0 THEN
        CREATE INDEX quiz_quizxquestion_ind ON quiz_quizxquestion (quiz_id, question_id);
    END IF;
END $$
DELIMITER ;

CALL create_quiz_quizxquestion_ind();
DROP PROCEDURE create_quiz_quizxquestion_ind;


/**
 * Quiz answers table
 */
--
-- Name: quiz_answers; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS quiz_answers (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    quizxquestion_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    answer longtext,
    points numeric(4,0),
    created_at timestamp DEFAULT current_timestamp,
    modified_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: quiz_answers_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_quiz_answers_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='quiz_answers'
    AND index_name='quiz_answers_ind';

    IF index_exists=0 THEN
        CREATE INDEX quiz_answers_ind ON quiz_answers (quizxquestion_id, student_id);
    END IF;
END $$
DELIMITER ;

CALL create_quiz_answers_ind();
DROP PROCEDURE create_quiz_answers_ind;


/**
 * Questions table
 */
--
-- Name: questions; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS quiz_questions (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title text NOT NULL,
    type varchar(10),
    category_id integer NOT NULL,
    description longtext,
    sort_order numeric,
    answer text,
    file text,
    created_at timestamp DEFAULT current_timestamp,
    created_by integer NOT NULL
);


--
-- Name: quiz_questions_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_quiz_questions_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='quiz_questions'
    AND index_name='quiz_questions_ind';

    IF index_exists=0 THEN
        CREATE INDEX quiz_questions_ind ON quiz_questions (school_id);
    END IF;
END $$
DELIMITER ;

CALL create_quiz_questions_ind();
DROP PROCEDURE create_quiz_questions_ind;


/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS quiz_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    color varchar(255)
);


--
-- Name: quiz_categories_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_quiz_categories_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='quiz_categories'
    AND index_name='quiz_categories_ind';

    IF index_exists=0 THEN
        CREATE INDEX quiz_categories_ind ON quiz_categories (school_id);
    END IF;
END $$
DELIMITER ;

CALL create_quiz_categories_ind();
DROP PROCEDURE create_quiz_categories_ind;
