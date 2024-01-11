/**
 * Install MySQL
 * - Add initcap() function to convert first letter of each word to uppercase
 * @link https://roytuts.com/mysql-function-to-capitalize-the-first-letter-of-words-in-a-string/
 *
 * @package Automatic Attendance
 */

DROP FUNCTION IF EXISTS initcap;

DELIMITER $$
CREATE FUNCTION initcap(input varchar(255)) RETURNS varchar(255)	
BEGIN
	DECLARE len integer;
	DECLARE i integer;

	SET len   = CHAR_LENGTH(input);
	SET input = LOWER(input);
	SET i = 0;

	WHILE (i < len) DO
		IF (MID(input,i,1) = ' ' OR i = 0) THEN
			IF (i < len) THEN
				SET input = CONCAT(
					LEFT(input,i),
					UPPER(MID(input,i + 1,1)),
					RIGHT(input,len - i - 1)
				);
			END IF;
		END IF;
		SET i = i + 1;
	END WHILE;

	RETURN input;
END$$
DELIMITER ;
