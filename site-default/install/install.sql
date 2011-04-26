DROP TABLE IF EXISTS `field_body`;
CREATE TABLE `field_body` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (27,'<h3>The page you were looking for is not found.</h3>\r\n<p>Please use our search engine or navigation above to find the page.</p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (1,'<h2>What is ProcessWire?</h2><p>ProcessWire gives you full control over your fields, templates and markup. It provides a powerful template system that works the way you do. Not to mention, ProcessWire\'s API makes working with your content easy and enjoyable. <a href=\"http://processwire.com\">Learn more</a> </p><h2>Basic Site Profile</h2><p>This is a basic starter site for you to use in developing your own site. There are a few pages here to serve as examples, but this site profile does not make any attempt to demonstrate all that ProcessWire can do. For a more full featured example site, you may want to install the <a href=\"http://www.processwire.com/download/\" target=\"_blank\">skyscrapers profile</a>. But if you are building a new site, then you are in the right place with this basic site profile. You may use these existing templates and design as they are, or you may replace them entirely. <a href=\"/sky/templates/\">Read more</a></p><h2>Browse the Site</h2>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5743,'<h2>Ut capio feugiat saepius torqueo olim</h2><h3>In utinam facilisi eum vicis feugait nimis</h3><p>Iusto incassum appellatio cui macto genitus vel. Lobortis aliquam luctus, roto enim, imputo wisi tamen. Ratis odio, genitus acsi, neo illum consequat consectetuer ut. </p><p>Wisi fere virtus cogo, ex ut vel nullus similis vel iusto. Tation incassum adsum in, quibus capto premo diam suscipere facilisi. Uxor laoreet mos capio premo feugait ille et. Pecus abigo immitto epulae duis vel. Neque causa, indoles verto, decet ingenium dignissim. </p><p>Patria iriure vel vel autem proprius indoles ille sit. Tation blandit refoveo, accumsan ut ulciscor lucidus inhibeo capto aptent opes, foras. </p><h3>Dolore ea valde refero feugait utinam luctus</h3><p>Usitas, nostrud transverbero, in, amet, nostrud ad. Ex feugiat opto diam os aliquam regula lobortis dolore ut ut quadrum. Esse eu quis nunc jugis iriure volutpat wisi, fere blandit inhibeo melior, hendrerit, saluto velit. Eu bene ideo dignissim delenit accumsan nunc. Usitas ille autem camur consequat typicus feugait elit ex accumsan nutus accumsan nimis pagus, occuro. Immitto populus, qui feugiat opto pneum letalis paratus. Mara conventio torqueo nibh caecus abigo sit eum brevitas. Populus, duis ex quae exerci hendrerit, si antehabeo nobis, consequat ea praemitto zelus. </p><p>Immitto os ratis euismod conventio erat jus caecus sudo. Appellatio consequat, et ibidem ludus nulla dolor augue abdo tego euismod plaga lenis. Sit at nimis venio venio tego os et pecus enim pneum magna nobis ad pneum. Saepius turpis probo refero molior nonummy aliquam neque appellatio jus luctus acsi. Ulciscor refero pagus imputo eu refoveo valetudo duis dolore usitas. Consequat suscipere quod torqueo ratis ullamcorper, dolore lenis, letalis quia quadrum plaga minim. </p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5748,'<h2>The site template files are located in /site/templates/</h2><p>Each of the template files in this site profile includes the header template (head.inc), outputs the bodycopy, and then includes the footer template (foot.inc). This is to avoid duplication of the markup that is the same across all pages in the site. This is just one strategy you can use for templates. </p><p>You could of course make each template completely self contained with it\'s own markup, but if you have more than one template with some of the same markup, then it wouldn\'t be very efficient to do that.</p><p>Another strategy would be to use a have a main template that contains all your markup and has placeholder variables for the dynamic parts. Then your other templates would populate the placeholder variables before including the main template. See the <a href=\"http://processwire.com/download/\">skyscrapers</a> site profile for an example of that strategy. </p><p>Regardless of what strategy you use in your own site, I hope that you find ProcessWire easy to develop with. See the <a href=\"http://processwire.com/api/\">Developer API</a>, and the section on <a href=\"http://processwire.com/api/templates/\">Templates</a> to get you started.</p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5740,'<h2>Si lobortis singularis genitus ibidem saluto.</h2><p>Dolore ad nunc, mos accumsan paratus duis suscipit luptatum facilisis macto uxor iaceo quadrum. Demoveo, appellatio elit neque ad commodo ea. Wisi, iaceo, tincidunt at commoveo rusticus et, ludus. Feugait at blandit bene blandit suscipere abdo duis ideo bis commoveo pagus ex, velit. Consequat commodo roto accumsan, duis transverbero.</p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5763,'<h2>Pertineo vel dignissim, natu letalis fere odio</h2><h3>Si lobortis singularis genitus ibidem saluto</h3><p>Magna in gemino, gilvus iusto capto jugis abdo mos aptent acsi qui. Utrum inhibeo humo humo duis quae. Lucidus paulatim facilisi scisco quibus hendrerit conventio adsum. Feugiat eligo foras ex elit sed indoles hos elit ex antehabeo defui et nostrud. Letatio valetudo multo consequat inhibeo ille dignissim pagus et in quadrum eum eu. Aliquam si consequat, ut nulla amet et turpis exerci, adsum luctus ne decet, delenit. Commoveo nunc diam valetudo cui, aptent commoveo at obruo uxor nulla aliquip augue. </p><p>Iriure, ex velit, praesent vulpes delenit capio vero gilvus inhibeo letatio aliquip metuo qui eros. Transverbero demoveo euismod letatio torqueo melior. Ut odio in suscipit paulatim amet huic letalis suscipere eros causa, letalis magna. </p>');
DROP TABLE IF EXISTS `field_email`;
CREATE TABLE `field_email` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`pages_id`),
  KEY `data_exact` (`data`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_email` (`pages_id`, `data`) VALUES (5805,'');
INSERT INTO `field_email` (`pages_id`, `data`) VALUES (5803,'');

DROP TABLE IF EXISTS `field_headline`;
CREATE TABLE `field_headline` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (1,'Basic Example Site');
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (5740,'');
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (5743,'');
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (5748,'Developing Site Templates');
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (5763,'');

DROP TABLE IF EXISTS `field_images`;
CREATE TABLE `field_images` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` varchar(255) NOT NULL,
  `sort` int(10) unsigned NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`pages_id`,`sort`),
  KEY `data` (`data`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'westin_interior2.jpg',7,'Westin Peachtree Atlanta hotel lobby area.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'marquis_interior13b_med.jpg',6,'Atrium at the Atlanta Marriott Marquis hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'marquis_interior7b.jpg',5,'Elevator at the Atlanta Marriott Marquis hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'marquis_interior3.jpg',4,'Elevator core at the Atlanta Marriott Marquis hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'hyatt_interior11.jpg',3,'Looking up from the lobby area at the Atlanta Hyatt hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'hyatt2.jpg',2,'Detail from Atlanta Hyatt Hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'hyatt_interior9.jpg',1,'Detail from Atlanta Hyatt Hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1,'westin_interior1.jpg',0,'Westin Peachtree Atlanta hotel lobby area.');
DROP TABLE IF EXISTS `field_pass`;
CREATE TABLE `field_pass` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` char(40) NOT NULL,
  `salt` char(32) NOT NULL,
  PRIMARY KEY  (`pages_id`),
  KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;
INSERT INTO `field_pass` (`pages_id`, `data`, `salt`) VALUES (40,'','');
INSERT INTO `field_pass` (`pages_id`, `data`, `salt`) VALUES (5803,'aaa','aaaa');
INSERT INTO `field_pass` (`pages_id`, `data`, `salt`) VALUES (5805,'bbb','bbbb');
DROP TABLE IF EXISTS `field_permissions`;
CREATE TABLE `field_permissions` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` int(11) NOT NULL,
  `sort` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pages_id`,`sort`),
  KEY `data` (`data`,`pages_id`,`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,32,2);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,32,2);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,33,1);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,33,1);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,34,3);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,34,3);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,35,4);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,35,4);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (37,36,0);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,36,0);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,36,0);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5781,5);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,5781,5);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5782,6);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,5782,6);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5785,7);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5787,8);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5788,9);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5790,10);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5791,11);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5792,12);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (38,5808,13);
INSERT INTO `field_permissions` (`pages_id`, `data`, `sort`) VALUES (5802,5808,7);
DROP TABLE IF EXISTS `field_process`;
CREATE TABLE `field_process` (
  `pages_id` int(11) NOT NULL default '0',
  `data` int(11) NOT NULL default '0',
  PRIMARY KEY  (`pages_id`),
  KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (6,17);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (3,12);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (8,12);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (9,14);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (10,7);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (11,47);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (16,48);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5722,104);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (21,50);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (29,66);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (23,10);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5807,138);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (31,136);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (22,76);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (30,68);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5733,129);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (2,87);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (2016,104);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5731,121);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5729,109);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (28,76);
DROP TABLE IF EXISTS `field_roles`;
CREATE TABLE `field_roles` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` int(11) NOT NULL,
  `sort` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pages_id`,`sort`),
  KEY `data` (`data`,`pages_id`,`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_roles` (`pages_id`, `data`, `sort`) VALUES (40,37,0);
INSERT INTO `field_roles` (`pages_id`, `data`, `sort`) VALUES (41,37,0);
INSERT INTO `field_roles` (`pages_id`, `data`, `sort`) VALUES (41,38,2);
INSERT INTO `field_roles` (`pages_id`, `data`, `sort`) VALUES (5803,5802,0);
INSERT INTO `field_roles` (`pages_id`, `data`, `sort`) VALUES (5805,5802,0);
DROP TABLE IF EXISTS `field_sidebar`;
CREATE TABLE `field_sidebar` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_sidebar` (`pages_id`, `data`) VALUES (1,'<h3>About ProcessWire</h3><p>ProcessWire is an open source CMS and web application framework aimed at the needs of designers, developers and their clients. </p><p><a href=\"http://processwire.com/about/\" target=\"_blank\">About ProcessWire</a><br /><a href=\"http://processwire.com/api/\">Developer API</a><br /><a href=\"http://processwire.com/contact/\">Contact Us</a><br /><a href=\"http://twitter.com/rc_d\">Follow Us on Twitter</a></p>');
INSERT INTO `field_sidebar` (`pages_id`, `data`) VALUES (5743,'<h3>Sudo nullus</h3><p>Et torqueo vulpes vereor luctus augue quod consectetuer antehabeo causa patria tation ex plaga ut. Abluo delenit wisi iriure eros feugiat probo nisl aliquip nisl, patria. Antehabeo esse camur nisl modo utinam. Sudo nullus ventosus ibidem facilisis saepius eum sino pneum, vicis odio voco opto.</p>');
DROP TABLE IF EXISTS `field_summary`;
CREATE TABLE `field_summary` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5743,'Dolore ea valde refero feugait utinam luctus. Probo velit commoveo et, delenit praesent, suscipit zelus, hendrerit zelus illum facilisi, regula. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5740,'This is a placeholder page with two child pages to serve as an example. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5765,'View this template\'s source for a demonstration of how to create a basic site map. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5748,'More about the templates included in this basic site profile. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5763,'Mos erat reprobo in praesent, mara premo, obruo iustum pecus velit lobortis te sagaciter populus.');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (1,'ProcessWire is an open source CMS and web application framework aimed at the needs of designers, developers and their clients. ');
DROP TABLE IF EXISTS `field_title`;
CREATE TABLE `field_title` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY  (`pages_id`),
  KEY `data_exact` (`data`(255)),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (14,'Edit Template');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (15,'Add Template');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (12,'Templates');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (11,'Templates');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (19,'Field groups');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (20,'Edit Fieldgroup');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (16,'Fields');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (17,'Fields');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (18,'Edit Field');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (22,'Setup');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (3,'Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (6,'Add Page');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (8,'Page List');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (9,'Save Sort');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (10,'Edit Page');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (21,'Modules');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (29,'Users');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (30,'Roles');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (1,'Home');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (2,'Admin');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (7,'Trash');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (27,'404 Page Not Found');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5731,'Insert Link');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (23,'Login');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5807,'Profile');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5729,'Empty Trash');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5740,'About');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5743,'Child page example 1');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5722,'Search');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5724,'Search');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5733,'Insert Image');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5748,'Templates');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5763,'Child page example 2');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (33,'Add New Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5765,'Site Map');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (28,'Access');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5783,'page-trash-empty');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5784,'page-trash-view');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (31,'Permissions');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (32,'Edit Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (34,'Delete Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (35,'Move Pages (Change Parent)');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (36,'View Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5781,'Sort Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5782,'Change Templates on Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5785,'Administer Modules');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5787,'Administer Fields');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5788,'Administer Templates');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5790,'Administer Users');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5791,'Administer Roles');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5792,'Administer Permissions');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5808,'User can update their own profile/password');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5810,'test');
DROP TABLE IF EXISTS `fieldgroups`;
CREATE TABLE `fieldgroups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) character set ascii NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=95 DEFAULT CHARSET=utf8;
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (2,'admin');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (88,'sitemap');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (83,'page');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (80,'search');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (3,'user');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (4,'role');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (5,'permission');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (94,'home');
DROP TABLE IF EXISTS `fieldgroups_fields`;
CREATE TABLE `fieldgroups_fields` (
  `fieldgroups_id` int(10) unsigned NOT NULL default '0',
  `fields_id` int(10) unsigned NOT NULL default '0',
  `sort` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`fieldgroups_id`,`fields_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (94,1,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (2,2,1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (88,1,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (94,44,5);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (94,76,3);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (88,79,1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (80,1,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83,1,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83,44,5);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83,76,3);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83,78,1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (2,1,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (3,4,2);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (3,92,1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (4,5,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (94,79,2);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83,79,2);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83,82,4);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (5,1,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (3,3,0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (94,78,1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (94,82,4);

DROP TABLE IF EXISTS `fields`;
CREATE TABLE `fields` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` varchar(128) character set ascii NOT NULL,
  `name` varchar(255) character set ascii NOT NULL,
  `flags` int(11) NOT NULL default '0',
  `label` varchar(255) NOT NULL default '',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=MyISAM AUTO_INCREMENT=95 DEFAULT CHARSET=utf8;

INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (1,'FieldtypePageTitle','title',13,'Title','{\"description\":\"\",\"required\":1,\"collapsed\":0,\"textformatters\":[\"TextformatterEntities\"],\"size\":0,\"maxlength\":255}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (2,'FieldtypeModule','process',9,'Process','{\"attributes\":[],\"description\":\"The process that is executed on this page. This field is required by the admin, so you should not delete or rename it. \",\"collapsed\":0,\"required\":1,\"delete\":\"\",\"moduleTypes\":[\"Process\"],\"instantiateModule\":\"\",\"system\":\"\",\"permanent\":1}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (82,'FieldtypeTextarea','sidebar',0,'Sidebar','{\"textformatters\":[],\"inputfieldClass\":\"InputfieldTinyMCE\",\"collapsed\":0,\"rows\":5}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (44,'FieldtypeImage','images',0,'Images','{\"extensions\":\"gif jpg jpeg png\",\"maxFiles\":0,\"entityEncode\":1,\"collapsed\":0,\"required\":\"\",\"unzip\":1,\"descriptionRows\":1,\"adminThumbs\":1}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (79,'FieldtypeTextarea','summary',1,'Summary','{\"textformatters\":[\"TextformatterMarkdownExtra\",\"TextformatterSmartypants\",\"TextformatterPstripper\"],\"inputfieldClass\":\"InputfieldTextarea\",\"collapsed\":0,\"rows\":3}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (76,'FieldtypeTextarea','body',0,'Body','{\"textformatters\":[],\"inputfieldClass\":\"InputfieldTinyMCE\",\"collapsed\":0,\"required\":\"\",\"rows\":10,\"theme_advanced_buttons1\":\"formatselect,|,bold,italic,|,bullist,numlist,|,link,unlink,|,image,|,code,|,fullscreen\",\"theme_advanced_buttons2\":false,\"theme_advanced_blockformats\":\"p,h2,h3,h4,blockquote,pre\",\"plugins\":\"inlinepopups,safari,media,paste,fullscreen\",\"valid_elements\":\"@[id|class],a[href|target|name],strong\\/b,em\\/i,br,img[src|id|class|width|height|alt],ul,ol,li,p[class],h2,h3,h4,blockquote,-p,-table[border=0|cellspacing|cellpadding|width|frame|rules|height|align|summary|bgcolor|background|bordercolor],-tr[rowspan|width|height|align|valign|bgcolor|background|bordercolor],tbody,thead,tfoot,#td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor|scope],#th[colspan|rowspan|width|height|align|valign|scope],code,pre\"}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (78,'FieldtypeText','headline',0,'Headline','{\"description\":\"Use this instead of the Title if a longer headline is needed than what you want to appear in navigation. \",\"textformatters\":[\"TextformatterEntities\"],\"collapsed\":2,\"size\":0,\"maxlength\":1024}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (3,'FieldtypePassword','pass',24,'Set Password','{\"collapsed\":\"1\",\"size\":50,\"maxlength\":128,\"required\":\"\"}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (5,'FieldtypePage','permissions',24,'Permissions','{\"derefAsPage\":0,\"collapsed\":0,\"parent_id\":31,\"template_id\":\"\",\"findPagesCode\":\"\",\"labelFieldName\":\"title\",\"inputfield\":\"InputfieldCheckboxes\",\"required\":\"\"}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (4,'FieldtypePage','roles',24,'Roles','{\"derefAsPage\":0,\"collapsed\":0,\"parent_id\":30,\"template_id\":\"\",\"findPagesCode\":\"\",\"labelFieldName\":\"name\",\"inputfield\":\"InputfieldCheckboxes\",\"description\":\"User will inherit the permissions assigned to each role. You may assign multiple roles to a user. When accessing a page, the user will only inherit permissions from the roles that are also assigned to the page\'s template. \",\"required\":\"\"}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (92,'FieldtypeEmail','email',9,'E-Mail Address','{\"textformatters\":[],\"collapsed\":0,\"required\":\"\",\"size\":70,\"maxlength\":255}');

DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `class` varchar(128) character set ascii NOT NULL,
  `flags` int(11) NOT NULL default '0',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `class` (`class`)
) ENGINE=MyISAM AUTO_INCREMENT=140 DEFAULT CHARSET=utf8;

INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (1,'FieldtypeTextarea',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (2,'FieldtypeNumber',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (3,'FieldtypeText',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (4,'FieldtypePage',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (30,'InputfieldForm',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (6,'FieldtypeFile',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (7,'ProcessPageEdit',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (10,'ProcessLogin',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (11,'ProcessLogout',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (12,'ProcessPageList',0,'{\"pageLabelField\":\"title\",\"paginationLimit\":25,\"limit\":50}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (121,'ProcessPageEditLink',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (14,'ProcessPageSort',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (15,'InputfieldPageListSelect',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (117,'JqueryUI',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (17,'ProcessPageAdd',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (125,'SessionLoginThrottle',3,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (122,'InputfieldPassword',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (25,'InputfieldAsmSelect',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (116,'JqueryCore',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (27,'FieldtypeModule',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (28,'FieldtypeDatetime',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (29,'FieldtypeEmail',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (108,'InputfieldURL',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (32,'InputfieldSubmit',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (33,'InputfieldWrapper',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (34,'InputfieldText',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (35,'InputfieldTextarea',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (36,'InputfieldSelect',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (37,'InputfieldCheckbox',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (38,'InputfieldCheckboxes',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (39,'InputfieldRadios',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (40,'InputfieldHidden',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (41,'InputfieldName',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (43,'InputfieldSelectMultiple',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (45,'JqueryWireTabs',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (46,'ProcessPage',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (47,'ProcessTemplate',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (48,'ProcessField',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (50,'ProcessModule',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (114,'PagePermissions',3,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (97,'FieldtypeCheckbox',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (115,'PageRender',3,'{\"clearCache\":1}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (55,'InputfieldFile',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (56,'InputfieldImage',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (57,'FieldtypeImage',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (95,'FieldtypeCache',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (60,'InputfieldPage',0,'{\"inputfieldClasses\":[\"InputfieldSelect\",\"InputfieldSelectMultiple\",\"InputfieldCheckboxes\",\"InputfieldRadios\",\"InputfieldAsmSelect\",\"InputfieldPageListSelect\",\"InputfieldPageListSelectMultiple\"]}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (61,'TextformatterEntities',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (62,'TextformatterMarkdownExtra',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (63,'TextformatterSmartypants',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (64,'TextformatterPstripper',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (65,'TextformatterNewlineUL',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (66,'ProcessUser',0,'{\"showFields\":[\"name\",\"email\",\"roles\"]}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (67,'MarkupAdminDataTable',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (68,'ProcessRole',0,'{\"showFields\":[\"name\"]}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (76,'ProcessList',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (77,'ModuleTest',2,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (78,'InputfieldFieldset',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (79,'InputfieldMarkup',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (80,'InputfieldEmail',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (89,'FieldtypeFloat',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (83,'ProcessPageView',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (84,'FieldtypeInteger',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (85,'InputfieldInteger',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (86,'InputfieldPageName',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (87,'ProcessHome',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (90,'InputfieldFloat',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (92,'InputfieldTinyMCE',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (94,'InputfieldDatetime',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (98,'MarkupPagerNav',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (132,'FieldtypeComments',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (129,'ProcessPageEditImageSelect',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (101,'InputfieldCommentsAdmin',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (102,'JqueryFancybox',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (103,'JqueryTableSorter',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (104,'ProcessPageSearch',1,'{\"searchFields\":\"title body\",\"displayField\":\"title path\"}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (105,'FieldtypeFieldsetOpen',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (106,'FieldtypeFieldsetClose',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (107,'FieldtypeFieldsetTabOpen',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (109,'ProcessPageTrash',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (111,'FieldtypePageTitle',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (112,'InputfieldPageTitle',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (113,'MarkupPageArray',3,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (130,'MarkupTwitterFeed',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (131,'InputfieldButton',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (133,'FieldtypePassword',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (134,'ProcessPageType',1,'{\"showFields\":[]}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (135,'FieldtypeURL',1,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (136,'ProcessPermission',1,'{\"showFields\":[\"name\",\"title\"]}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (137,'InputfieldPageListSelectMultiple',0,'');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (138,'ProcessProfile',1,'{\"profileFields\":[\"pass\",\"email\"]}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (139,'ProcessForgotPassword',1,'');
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` int(11) unsigned NOT NULL default '0',
  `templates_id` int(11) unsigned NOT NULL default '0',
  `name` varchar(128) character set ascii NOT NULL,
  `status` int(10) unsigned NOT NULL default '1',
  `modified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `modified_users_id` int(10) unsigned NOT NULL default '2',
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `created_users_id` int(10) unsigned NOT NULL default '2',
  `sort` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name_parent_id` (`name`,`parent_id`),
  KEY `parent_id` (`parent_id`),
  KEY `templates_id` (`templates_id`),
  KEY `modified` (`modified`),
  KEY `created` (`created`),
  KEY `status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=5811 DEFAULT CHARSET=utf8;
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (1,0,40,'home',9,'2011-04-19 17:52:39',41,'0000-00-00 00:00:00',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (2,1,2,'processwire',1039,'2011-03-29 17:27:33',41,'0000-00-00 00:00:00',2,6);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (3,2,2,'page',21,'2011-03-29 17:37:06',41,'0000-00-00 00:00:00',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5790,31,5,'user-admin',25,'2011-04-04 21:34:50',41,'2011-03-29 20:06:47',41,10);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (6,3,2,'add',21,'2011-03-29 17:37:06',41,'0000-00-00 00:00:00',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (7,1,2,'trash',1039,'2011-03-29 17:27:33',41,'2010-02-07 00:29:39',2,7);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (8,3,2,'list',21,'2011-03-29 17:37:06',41,'0000-00-00 00:00:00',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (9,3,2,'sort',23,'2011-03-29 17:37:06',41,'0000-00-00 00:00:00',2,2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (10,3,2,'edit',21,'2011-03-29 17:37:06',41,'0000-00-00 00:00:00',2,3);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (11,22,2,'template',21,'2011-03-29 17:37:06',41,'2010-02-01 06:04:54',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (16,22,2,'field',21,'2011-03-29 17:37:06',41,'2010-02-01 07:44:07',2,2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (21,2,2,'module',21,'2011-03-29 17:37:06',41,'2010-02-02 05:02:24',2,2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (22,2,2,'setup',21,'2011-03-29 17:37:06',41,'2010-02-09 07:16:59',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (23,2,2,'login',1035,'2011-03-29 17:27:33',41,'2010-02-17 04:59:39',2,6);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5807,2,2,'profile',1025,'2011-04-25 20:01:37',41,'2011-04-25 19:57:18',41,5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5791,31,5,'role-admin',25,'2011-04-04 21:34:28',41,'2011-03-29 20:06:47',41,11);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (27,1,29,'http404',1035,'2011-04-04 19:13:20',41,'2010-06-03 02:53:03',3,4);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5733,3,2,'image',17,'2011-03-29 17:37:06',41,'2010-10-12 23:56:48',2,7);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5740,1,29,'about',1,'2011-04-19 17:51:26',41,'2010-10-25 18:39:33',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5743,5740,29,'what',1,'2011-04-26 19:01:44',41,'2010-10-25 19:21:34',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5724,1,26,'search',1025,'2010-11-30 16:17:07',2,'2010-09-06 01:05:28',2,3);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5722,3,2,'search',21,'2011-03-29 17:37:06',41,'2010-08-04 01:23:59',2,5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5731,3,2,'link',17,'2011-03-29 17:37:06',41,'2010-10-01 01:03:56',2,6);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5729,3,2,'trash',23,'2011-03-29 17:37:06',41,'2010-09-28 01:39:30',2,5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5748,1,29,'templates',1,'2010-12-05 17:27:01',2,'2010-10-25 21:59:44',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5765,1,34,'site-map',1,'2011-02-21 18:40:06',2,'2010-11-30 16:16:49',2,2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5763,5740,29,'background',1,'2011-04-08 21:20:24',5796,'2010-11-29 17:11:36',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (28,2,2,'access',13,'2011-04-04 20:40:40',41,'2011-03-19 15:14:20',2,4);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (29,28,2,'users',29,'2011-04-04 20:39:08',41,'2011-03-19 15:15:29',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (30,28,2,'roles',29,'2011-04-04 20:38:39',41,'2011-03-19 15:15:45',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (31,28,2,'permissions',29,'2011-04-04 20:53:52',41,'2011-03-19 15:16:00',2,2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (32,31,5,'page-edit',25,'2011-04-07 18:06:13',41,'2011-03-19 15:17:03',2,2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (33,31,5,'page-add',25,'2011-04-07 18:06:13',41,'2011-03-19 15:17:14',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (34,31,5,'page-delete',25,'2011-04-07 18:06:13',41,'2011-03-19 15:17:23',2,3);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (35,31,5,'page-move',25,'2011-04-07 18:06:13',41,'2011-03-19 15:17:41',2,4);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (36,31,5,'page-view',25,'2011-04-07 18:06:13',41,'2011-03-19 15:17:57',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (37,30,4,'guest',25,'2011-04-04 21:37:19',41,'2011-03-19 15:18:41',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (38,30,4,'superuser',25,'2011-04-25 20:04:53',41,'2011-03-19 15:18:55',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (41,29,3,'ryan',1,'2011-04-25 20:29:47',41,'2011-03-19 15:41:26',2,0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (40,29,3,'guest',25,'2011-04-06 22:49:52',41,'2011-03-20 13:31:59',2,1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5802,30,4,'test-role',1,'2011-04-26 18:51:49',41,'2011-04-08 21:26:34',5796,2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5781,31,5,'page-sort',25,'2011-04-08 19:14:38',41,'2011-03-26 18:04:50',41,5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5782,31,5,'page-template',25,'2011-04-17 19:39:48',41,'2011-03-26 18:25:31',41,6);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5805,29,3,'karen',1,'2011-04-26 18:53:02',41,'2011-04-23 15:10:30',41,3);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5785,31,5,'module-admin',25,'2011-04-04 21:33:21',41,'2011-03-26 18:54:25',41,7);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5787,31,5,'field-admin',25,'2011-04-04 21:33:33',41,'2011-03-28 17:49:36',41,8);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5788,31,5,'template-admin',25,'2011-04-17 19:37:12',41,'2011-03-28 17:51:41',41,9);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5792,31,5,'permission-admin',25,'2011-04-04 21:34:37',41,'2011-03-29 20:06:47',41,12);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5803,29,3,'test-user',1,'2011-04-26 18:55:58',40,'2011-04-08 21:27:32',5796,4);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5808,31,5,'profile-edit',1,'2011-04-25 20:07:14',41,'2011-04-25 20:02:22',41,13);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5810,7,29,'5810_test',8193,'2011-04-26 19:02:04',41,'2011-04-26 19:01:57',41,0);
DROP TABLE IF EXISTS `pages_access`;
CREATE TABLE `pages_access` (
  `pages_id` int(11) NOT NULL,
  `templates_id` int(11) NOT NULL,
  `ts` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`pages_id`),
  KEY `templates_id` (`templates_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (40,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (41,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5803,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5805,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (37,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (38,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5802,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (32,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (33,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (34,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (35,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (36,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5781,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5782,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5785,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5787,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5788,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5790,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5791,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5792,2,'2011-04-25 13:57:07');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5808,2,'2011-04-25 20:02:22');
INSERT INTO `pages_access` (`pages_id`, `templates_id`, `ts`) VALUES (5810,2,'2011-04-26 19:02:04');
DROP TABLE IF EXISTS `pages_parents`;
CREATE TABLE `pages_parents` (
  `pages_id` int(10) unsigned NOT NULL,
  `parents_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pages_id`,`parents_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (2,1);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (3,1);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (3,2);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (7,1);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (22,1);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (22,2);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (29,1);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (29,2);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (29,28);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (30,1);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (30,2);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (30,28);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (31,1);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (31,2);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (31,28);
INSERT INTO `pages_parents` (`pages_id`, `parents_id`) VALUES (5740,1);
DROP TABLE IF EXISTS `pages_sortfields`;
CREATE TABLE `pages_sortfields` (
  `pages_id` int(10) unsigned NOT NULL default '0',
  `sortfield` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`pages_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `process_forgot_password`;
CREATE TABLE `process_forgot_password` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `token` char(32) NOT NULL,
  `ts` int(10) unsigned NOT NULL,
  `ip` varchar(15) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `token` (`token`),
  KEY `ts` (`ts`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;
DROP TABLE IF EXISTS `session_login_throttle`;
CREATE TABLE `session_login_throttle` (
  `name` varchar(128) NOT NULL,
  `attempts` int(10) unsigned NOT NULL default '0',
  `last_attempt` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `templates`;
CREATE TABLE `templates` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) character set ascii NOT NULL,
  `fieldgroups_id` int(10) unsigned NOT NULL default '0',
  `flags` int(11) NOT NULL default '0',
  `cache_time` mediumint(9) NOT NULL default '0',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `fieldgroups_id` (`fieldgroups_id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (2,'admin',2,8,0,'{\"useRoles\":1,\"allowPageNum\":1,\"redirectLogin\":23,\"slashUrls\":1,\"roles\":[]}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (29,'page',83,0,0,'{\"slashUrls\":1,\"roles\":[37,5802]}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (26,'search',80,0,0,'{\"allowPageNum\":1,\"slashUrls\":1,\"roles\":[37]}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (34,'sitemap',88,0,0,'{\"useRoles\":1,\"childrenTemplatesID\":-1,\"slashUrls\":1,\"roles\":[37,5802]}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (3,'user',3,8,0,'{\"childrenTemplatesID\":-1,\"slashUrls\":1,\"pageClass\":\"User\",\"noGlobal\":1,\"noMove\":1,\"noTrash\":1,\"noSettings\":1,\"noChangeTemplate\":1,\"nameContentTab\":1,\"roles\":[]}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (4,'role',4,8,0,'{\"childrenTemplatesID\":-1,\"slashUrls\":1,\"pageClass\":\"Role\",\"noGlobal\":1,\"noMove\":1,\"noTrash\":1,\"noSettings\":1,\"noChangeTemplate\":1,\"nameContentTab\":1,\"roles\":[]}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (5,'permission',5,8,0,'{\"childrenTemplatesID\":-1,\"slashUrls\":1,\"guestSearchable\":1,\"pageClass\":\"Permission\",\"noGlobal\":1,\"noMove\":1,\"noTrash\":1,\"noSettings\":1,\"noChangeTemplate\":1,\"nameContentTab\":1,\"roles\":[]}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `flags`, `cache_time`, `data`) VALUES (40,'home',94,0,0,'{\"useRoles\":1,\"slashUrls\":1,\"roles\":[37]}');
