/**********************************************************************
 install_es.sql file
 Optional, to translate texts to Spanish
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
SET title='Consumibles'
WHERE  title='Consumables'
AND category_type='CATEGORY';

UPDATE school_inventory_categories
SET title='Necesita un arreglo'
WHERE  title='Needs repair'
AND category_type='STATUS';

UPDATE school_inventory_categories
SET title='Comprar más'
WHERE  title='Buy more'
AND category_type='STATUS';

UPDATE school_inventory_categories
SET title='Prestado'
WHERE  title='Lent'
AND category_type='STATUS';
