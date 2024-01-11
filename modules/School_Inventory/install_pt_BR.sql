/**********************************************************************
 install_pt_BR.sql file
 Optional, to translate texts to Brazilian Portuguese.
 - Add templates
***********************************************************************/

--
-- Data for Name: school_inventory_categories; Type: TABLE DATA;
--

UPDATE school_inventory_categories
SET title='Computadores'
WHERE  title='Computers'
AND category_type='CATEGORY';

UPDATE school_inventory_categories
SET title='Consumíveis'
WHERE  title='Consumables'
AND category_type='CATEGORY';

UPDATE school_inventory_categories
SET title='Precisa de uma correção'
WHERE  title='Needs repair'
AND category_type='STATUS';

UPDATE school_inventory_categories
SET title='Compre mais'
WHERE  title='Buy more'
AND category_type='STATUS';

UPDATE school_inventory_categories
SET title='Emprestado'
WHERE  title='Lent'
AND category_type='STATUS';
