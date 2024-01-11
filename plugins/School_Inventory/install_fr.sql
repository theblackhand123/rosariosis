/**********************************************************************
 install_fr.sql file
 Optional, to translate texts to French
 - Add templates
***********************************************************************/

--
-- Data for Name: school_inventory_categories; Type: TABLE DATA;
--

UPDATE school_inventory_categories
SET title='Ordinateurs'
WHERE  title='Computers'
AND category_type='CATEGORY';

UPDATE school_inventory_categories
SET title='Consommables'
WHERE  title='Consumables'
AND category_type='CATEGORY';

UPDATE school_inventory_categories
SET title='À réparer'
WHERE  title='Needs repair'
AND category_type='STATUS';

UPDATE school_inventory_categories
SET title='En acheter plus'
WHERE  title='Buy more'
AND category_type='STATUS';

UPDATE school_inventory_categories
SET title='En prêt'
WHERE  title='Lent'
AND category_type='STATUS';
