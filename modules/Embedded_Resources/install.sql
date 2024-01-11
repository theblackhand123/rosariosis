
/**********************************************************************
 install.sql file
 Required as the module adds programs to other modules
 - Add profile exceptions for the module to appear in the menu
 - Add resources_embedded table
***********************************************************************/

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
SELECT 1, 'Embedded_Resources/EmbeddedResources.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Embedded_Resources/EmbeddedResources.php'
    AND profile_id=1);


--
-- Name: resources_embedded; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE resources_embedded (
    id serial PRIMARY KEY,
    title text NOT NULL,
    link text,
    published_grade_levels text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp
);
