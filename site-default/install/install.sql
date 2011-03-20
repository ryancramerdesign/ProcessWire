DROP TABLE IF EXISTS `fieldgroups`;
CREATE TABLE IF NOT EXISTS `fieldgroups` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=89 ;
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (2, 'admin');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (88, 'sitemap');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (83, 'page');
INSERT INTO `fieldgroups` (`id`, `name`) VALUES (80, 'search');
DROP TABLE IF EXISTS `fieldgroups_fields`;
CREATE TABLE IF NOT EXISTS `fieldgroups_fields` (
  `fieldgroups_id` int(10) unsigned NOT NULL default '0',
  `fields_id` int(10) unsigned NOT NULL default '0',
  `sort` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`fieldgroups_id`,`fields_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (2, 2, 1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (2, 1, 0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (88, 1, 0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83, 44, 5);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83, 82, 4);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (88, 79, 1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (80, 1, 0);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83, 76, 3);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83, 79, 2);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83, 78, 1);
INSERT INTO `fieldgroups_fields` (`fieldgroups_id`, `fields_id`, `sort`) VALUES (83, 1, 0);
DROP TABLE IF EXISTS `fields`;
CREATE TABLE IF NOT EXISTS `fields` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` varchar(128) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `flags` int(11) NOT NULL default '0',
  `label` varchar(255) NOT NULL default '',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=83 ;
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (1, 'FieldtypePageTitle', 'title', 5, 'Title', '{"description":"","required":1,"collapsed":0,"textformatters":["TextformatterEntities"],"size":0,"maxlength":255}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (2, 'FieldtypeModule', 'process', 1, 'Process', '{"attributes":[],"description":"The process that is executed on this page. This field is required by the admin, so you should not delete or rename it. ","collapsed":0,"required":"1","delete":"","moduleTypes":["Process"],"instantiateModule":""}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (82, 'FieldtypeTextarea', 'sidebar', 0, 'Sidebar', '{"textformatters":[],"inputfieldClass":"InputfieldTinyMCE","collapsed":0,"rows":5}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (44, 'FieldtypeImage', 'images', 0, 'Images', '{"extensions":"gif jpg jpeg png","maxFiles":0,"entityEncode":1,"collapsed":0,"required":"","unzip":1,"descriptionRows":1,"adminThumbs":1}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (79, 'FieldtypeTextarea', 'summary', 1, 'Summary', '{"textformatters":["TextformatterMarkdownExtra","TextformatterSmartypants","TextformatterPstripper"],"inputfieldClass":"InputfieldTextarea","collapsed":0,"rows":3}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (76, 'FieldtypeTextarea', 'body', 0, 'Body', '{"textformatters":[],"inputfieldClass":"InputfieldTinyMCE","collapsed":0,"required":"","rows":10,"theme_advanced_buttons1":"formatselect,|,bold,italic,|,bullist,numlist,|,link,unlink,|,image,|,code,|,fullscreen","theme_advanced_buttons2":false,"theme_advanced_blockformats":"p,h2,h3,h4,blockquote,pre","plugins":"inlinepopups,safari,media,paste,fullscreen","valid_elements":"@[id|class],a[href|target|name],strong\\/b,em\\/i,br,img[src|id|class|width|height|alt],ul,ol,li,p[class],h2,h3,h4,blockquote,-p,-table[border=0|cellspacing|cellpadding|width|frame|rules|height|align|summary|bgcolor|background|bordercolor],-tr[rowspan|width|height|align|valign|bgcolor|background|bordercolor],tbody,thead,tfoot,#td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor|scope],#th[colspan|rowspan|width|height|align|valign|scope],code,pre"}');
INSERT INTO `fields` (`id`, `type`, `name`, `flags`, `label`, `data`) VALUES (78, 'FieldtypeText', 'headline', 0, 'Headline', '{"description":"Use this instead of the Title if a longer headline is needed than what you want to appear in navigation. ","textformatters":["TextformatterEntities"],"collapsed":2,"size":0,"maxlength":1024}');
DROP TABLE IF EXISTS `field_body`;
CREATE TABLE IF NOT EXISTS `field_body` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (27, '<h3>The page you were looking for is not found.</h3>\r\n<p>Please use our search engine or navigation above to find the page.</p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (1, '<h2>What is ProcessWire?</h2><p>ProcessWire gives you full control over your fields, templates and markup. It provides a powerful template system that works the way you do. Not to mention, ProcessWire''s API makes working with your content easy and enjoyable. <a href="http://processwire.com">Learn more</a> </p><h2>Basic Site Profile</h2><p>This is a basic starter site for you to use in developing your own site. There are a few pages here to serve as examples, but this site profile does not make any attempt to demonstrate all that ProcessWire can do. For a more full featured example site, you may want to install the <a href="http://www.processwire.com/download/" target="_blank">skyscrapers profile</a>. But if you are building a new site, then you are in the right place with this basic site profile. You may use these existing templates and design as they are, or you may replace them entirely. <a href="templates/">Read more</a></p><h2>Browse the Site</h2>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5743, '<h2>Ut capio feugiat saepius torqueo olim</h2><h3>In utinam facilisi eum vicis feugait nimis</h3><p>Iusto incassum appellatio cui macto genitus vel. Lobortis aliquam luctus, roto enim, imputo wisi tamen. Ratis odio, genitus acsi, neo illum consequat consectetuer ut. </p><p>Wisi fere virtus cogo, ex ut vel nullus similis vel iusto. Tation incassum adsum in, quibus capto premo diam suscipere facilisi. Uxor laoreet mos capio premo feugait ille et. Pecus abigo immitto epulae duis vel. Neque causa, indoles verto, decet ingenium dignissim. </p><p>Patria iriure vel vel autem proprius indoles ille sit. Tation blandit refoveo, accumsan ut ulciscor lucidus inhibeo capto aptent opes, foras. </p><h3>Dolore ea valde refero feugait utinam luctus</h3><p>Usitas, nostrud transverbero, in, amet, nostrud ad. Ex feugiat opto diam os aliquam regula lobortis dolore ut ut quadrum. Esse eu quis nunc jugis iriure volutpat wisi, fere blandit inhibeo melior, hendrerit, saluto velit. Eu bene ideo dignissim delenit accumsan nunc. Usitas ille autem camur consequat typicus feugait elit ex accumsan nutus accumsan nimis pagus, occuro. Immitto populus, qui feugiat opto pneum letalis paratus. Mara conventio torqueo nibh caecus abigo sit eum brevitas. Populus, duis ex quae exerci hendrerit, si antehabeo nobis, consequat ea praemitto zelus. </p><p>Immitto os ratis euismod conventio erat jus caecus sudo. Appellatio consequat, et ibidem ludus nulla dolor augue abdo tego euismod plaga lenis. Sit at nimis venio venio tego os et pecus enim pneum magna nobis ad pneum. Saepius turpis probo refero molior nonummy aliquam neque appellatio jus luctus acsi. Ulciscor refero pagus imputo eu refoveo valetudo duis dolore usitas. Consequat suscipere quod torqueo ratis ullamcorper, dolore lenis, letalis quia quadrum plaga minim. </p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5748, '<h2>The site template files are located in /site/templates/</h2><p>Each of the template files in this site profile includes the header template (head.inc), outputs the bodycopy, and then includes the footer template (foot.inc). This is to avoid duplication of the markup that is the same across all pages in the site. This is just one strategy you can use for templates. </p><p>You could of course make each template completely self contained with it''s own markup, but if you have more than one template with some of the same markup, then it wouldn''t be very efficient to do that.</p><p>Another strategy would be to use a have a main template that contains all your markup and has placeholder variables for the dynamic parts. Then your other templates would populate the placeholder variables before including the main template. See the <a href="http://processwire.com/download/">skyscrapers</a> site profile for an example of that strategy. </p><p>Regardless of what strategy you use in your own site, I hope that you find ProcessWire easy to develop with. See the <a href="http://processwire.com/api/">Developer API</a>, and the section on <a href="http://processwire.com/api/templates/">Templates</a> to get you started.</p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5740, '<h2>Si lobortis singularis genitus ibidem saluto.</h2><p>Dolore ad nunc, mos accumsan paratus duis suscipit luptatum facilisis macto uxor iaceo quadrum. Demoveo, appellatio elit neque ad commodo ea. Wisi, iaceo, tincidunt at commoveo rusticus et, ludus. Feugait at blandit bene blandit suscipere abdo duis ideo bis commoveo pagus ex, velit. Consequat commodo roto accumsan, duis transverbero.</p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5763, '<h2>Pertineo vel dignissim, natu letalis fere odio</h2><h3>Si lobortis singularis genitus ibidem saluto</h3><p>Magna in gemino, gilvus iusto capto jugis abdo mos aptent acsi qui. Utrum inhibeo humo humo duis quae. Lucidus paulatim facilisi scisco quibus hendrerit conventio adsum. Feugiat eligo foras ex elit sed indoles hos elit ex antehabeo defui et nostrud. Letatio valetudo multo consequat inhibeo ille dignissim pagus et in quadrum eum eu. Aliquam si consequat, ut nulla amet et turpis exerci, adsum luctus ne decet, delenit. Commoveo nunc diam valetudo cui, aptent commoveo at obruo uxor nulla aliquip augue. </p><p>Iriure, ex velit, praesent vulpes delenit capio vero gilvus inhibeo letatio aliquip metuo qui eros. Transverbero demoveo euismod letatio torqueo melior. Ut odio in suscipit paulatim amet huic letalis suscipere eros causa, letalis magna. </p>');
INSERT INTO `field_body` (`pages_id`, `data`) VALUES (5764, '<p>test test test</p>');
DROP TABLE IF EXISTS `field_headline`;
CREATE TABLE IF NOT EXISTS `field_headline` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (1, 'Basic Example Site');
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (5740, 'About Us');
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (5743, 'Example #1');
INSERT INTO `field_headline` (`pages_id`, `data`) VALUES (5748, 'Developing Site Templates');
DROP TABLE IF EXISTS `field_images`;
CREATE TABLE IF NOT EXISTS `field_images` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` varchar(255) NOT NULL,
  `sort` int(10) unsigned NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`pages_id`,`sort`),
  KEY `data` (`data`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'westin_interior2.jpg', 7, 'Westin Peachtree Atlanta hotel lobby area.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'marquis_interior13b_med.jpg', 6, 'Atrium at the Atlanta Marriott Marquis hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'hyatt_interior11.jpg', 3, 'Looking up from the lobby area at the Atlanta Hyatt hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'marquis_interior7b.jpg', 5, 'Elevator at the Atlanta Marriott Marquis hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'marquis_interior3.jpg', 4, 'Elevator core at the Atlanta Marriott Marquis hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'hyatt2.jpg', 2, 'Detail from Atlanta Hyatt Hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'hyatt_interior9.jpg', 1, 'Detail from Atlanta Hyatt Hotel.');
INSERT INTO `field_images` (`pages_id`, `data`, `sort`, `description`) VALUES (1, 'westin_interior1.jpg', 0, 'Westin Peachtree Atlanta hotel lobby area.');
DROP TABLE IF EXISTS `field_process`;
CREATE TABLE IF NOT EXISTS `field_process` (
  `pages_id` int(11) NOT NULL default '0',
  `data` int(11) NOT NULL default '0',
  PRIMARY KEY  (`pages_id`),
  KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (6, 17);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (3, 12);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (8, 12);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (9, 14);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (10, 7);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (11, 47);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (16, 48);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5722, 104);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (21, 50);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5, 76);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (23, 10);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (24, 11);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (25, 68);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (22, 76);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (26, 66);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5733, 129);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (2, 87);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (2016, 104);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5731, 121);
INSERT INTO `field_process` (`pages_id`, `data`) VALUES (5729, 109);
DROP TABLE IF EXISTS `field_sidebar`;
CREATE TABLE IF NOT EXISTS `field_sidebar` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_sidebar` (`pages_id`, `data`) VALUES (1, '<h3>About ProcessWire</h3><p>ProcessWire is an open source CMS and web application framework aimed at the needs of designers, developers and their clients. </p><p><a href="http://processwire.com/about/" target="_blank">About ProcessWire</a><br /><a href="http://processwire.com/api/">Developer API</a><br /><a href="http://processwire.com/contact/">Contact Us</a><br /><a href="http://twitter.com/rc_d">Follow Us on Twitter</a></p>');
INSERT INTO `field_sidebar` (`pages_id`, `data`) VALUES (5743, '<h3>Sudo nullus</h3><p>Et torqueo vulpes vereor luctus augue quod consectetuer antehabeo causa patria tation ex plaga ut. Abluo delenit wisi iriure eros feugiat probo nisl aliquip nisl, patria. Antehabeo esse camur nisl modo utinam. Sudo nullus ventosus ibidem facilisis saepius eum sino pneum, vicis odio voco opto.</p>');
DROP TABLE IF EXISTS `field_summary`;
CREATE TABLE IF NOT EXISTS `field_summary` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5743, 'Dolore ea valde refero feugait utinam luctus. Probo velit commoveo et, delenit praesent, suscipit zelus, hendrerit zelus illum facilisi, regula. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5740, 'This is a placeholder page with two child pages to serve as an example. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5765, 'View this template''s source for a demonstration of how to create a basic site map. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5748, 'More about the templates included in this basic site profile. ');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (5763, 'Mos erat reprobo in praesent, mara premo, obruo iustum pecus velit lobortis te sagaciter populus.');
INSERT INTO `field_summary` (`pages_id`, `data`) VALUES (1, 'ProcessWire is an open source CMS and web application framework aimed at the needs of designers, developers and their clients. ');
DROP TABLE IF EXISTS `field_title`;
CREATE TABLE IF NOT EXISTS `field_title` (
  `pages_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY  (`pages_id`),
  FULLTEXT KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (14, 'Edit Template');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (15, 'Add Template');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (12, 'Templates');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (11, 'Templates');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (19, 'Field groups');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (20, 'Edit Fieldgroup');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (16, 'Fields');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (17, 'Fields');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (18, 'Edit Field');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (22, 'Setup');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (3, 'Pages');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (6, 'Add Page');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (8, 'Page List');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (9, 'Save Sort');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (10, 'Edit Page');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (21, 'Modules');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5, 'Access');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (25, 'Roles');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (26, 'Users');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (1, 'Home');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (2, 'Admin');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (7, 'Trash');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (27, '404 Page Not Found');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5731, 'Insert Link');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (23, 'Login');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (24, 'Logout');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5729, 'Empty Trash');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5740, 'About');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5743, 'Child page example 1');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5722, 'Search');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5724, 'Search');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5733, 'Insert Image');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5748, 'Templates');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5763, 'Child page example 2');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5764, 'Tertiary Page Test');
INSERT INTO `field_title` (`pages_id`, `data`) VALUES (5765, 'Site Map');
DROP TABLE IF EXISTS `modules`;
CREATE TABLE IF NOT EXISTS `modules` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `class` varchar(128) NOT NULL default '',
  `flags` int(11) NOT NULL default '0',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `class` (`class`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=132 ;
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (1, 'FieldtypeTextarea', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (2, 'FieldtypeNumber', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (3, 'FieldtypeText', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (4, 'FieldtypePage', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (30, 'InputfieldForm', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (6, 'FieldtypeFile', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (7, 'ProcessPageEdit', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (10, 'ProcessLogin', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (11, 'ProcessLogout', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (12, 'ProcessPageList', 0, '{"pageLabelField":"title","paginationLimit":25,"limit":50}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (121, 'ProcessPageEditLink', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (14, 'ProcessPageSort', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (15, 'InputfieldPageListSelect', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (117, 'JqueryUI', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (17, 'ProcessPageAdd', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (125, 'SessionLoginThrottle', 3, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (122, 'InputfieldPassword', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (25, 'InputfieldAsmSelect', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (116, 'JqueryCore', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (27, 'FieldtypeModule', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (28, 'FieldtypeDatetime', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (29, 'FieldtypeEmail', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (108, 'InputfieldURL', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (32, 'InputfieldSubmit', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (33, 'InputfieldWrapper', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (34, 'InputfieldText', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (35, 'InputfieldTextarea', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (36, 'InputfieldSelect', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (37, 'InputfieldCheckbox', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (38, 'InputfieldCheckboxes', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (39, 'InputfieldRadios', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (40, 'InputfieldHidden', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (41, 'InputfieldName', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (43, 'InputfieldSelectMultiple', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (45, 'JqueryWireTabs', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (46, 'ProcessPage', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (47, 'ProcessTemplate', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (48, 'ProcessField', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (50, 'ProcessModule', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (114, 'PagePermissions', 3, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (97, 'FieldtypeCheckbox', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (115, 'PageRender', 3, '{"clearCache":1}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (55, 'InputfieldFile', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (56, 'InputfieldImage', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (57, 'FieldtypeImage', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (95, 'FieldtypeCache', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (60, 'InputfieldPage', 0, '{"inputfieldClasses":["InputfieldAsmSelect","InputfieldCheckboxes","InputfieldPageListSelect","InputfieldRadios","InputfieldSelect","InputfieldSelectMultiple"]}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (61, 'TextformatterEntities', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (62, 'TextformatterMarkdownExtra', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (63, 'TextformatterSmartypants', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (64, 'TextformatterPstripper', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (65, 'TextformatterNewlineUL', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (66, 'ProcessUser', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (67, 'MarkupAdminDataTable', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (68, 'ProcessRole', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (76, 'ProcessList', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (77, 'ModuleTest', 2, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (78, 'InputfieldFieldset', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (79, 'InputfieldMarkup', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (80, 'InputfieldEmail', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (89, 'FieldtypeFloat', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (83, 'ProcessPageView', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (84, 'FieldtypeInteger', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (85, 'InputfieldInteger', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (86, 'InputfieldPageName', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (87, 'ProcessHome', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (90, 'InputfieldFloat', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (92, 'InputfieldTinyMCE', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (94, 'InputfieldDatetime', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (98, 'MarkupPagerNav', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (129, 'ProcessPageEditImageSelect', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (101, 'InputfieldCommentsAdmin', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (102, 'JqueryFancybox', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (103, 'JqueryTableSorter', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (104, 'ProcessPageSearch', 1, '{"searchFields":["title","body"],"displayField":"title path"}');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (105, 'FieldtypeFieldsetOpen', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (106, 'FieldtypeFieldsetClose', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (107, 'FieldtypeFieldsetTabOpen', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (109, 'ProcessPageTrash', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (111, 'FieldtypePageTitle', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (112, 'InputfieldPageTitle', 0, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (113, 'MarkupPageArray', 3, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (130, 'MarkupTwitterFeed', 1, '');
INSERT INTO `modules` (`id`, `class`, `flags`, `data`) VALUES (131, 'InputfieldButton', 0, '');
DROP TABLE IF EXISTS `pages`;
CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` int(11) unsigned NOT NULL default '0',
  `templates_id` int(11) unsigned NOT NULL default '0',
  `name` varchar(128) NOT NULL default '',
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5766 ;
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (1, 0, 29, 'home', 1, '2010-11-30 14:12:20', 2, '2011-03-02 12:13:50', 2, 0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (2, 1, 2, 'processwire', 1031, '2010-11-30 11:17:07', 2, '2011-03-02 12:13:50', 2, 5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (3, 2, 2, 'page', 5, '2010-09-26 22:36:21', 2, '2011-03-02 12:13:50', 2, 0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5, 2, 2, 'access', 5, '2010-09-27 23:52:08', 2, '2011-03-02 12:13:50', 2, 3);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (6, 3, 2, 'add', 5, '2010-09-26 22:36:32', 2, '2011-03-02 12:13:50', 2, 0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (7, 1, 2, 'trash', 1031, '2010-11-30 11:17:07', 2, '2011-03-02 12:13:50', 2, 6);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (8, 3, 2, 'list', 5, '2010-09-26 22:36:39', 2, '2011-03-02 12:13:50', 2, 1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (9, 3, 2, 'sort', 7, '2010-09-26 22:36:48', 2, '2011-03-02 12:13:50', 2, 2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (10, 3, 2, 'edit', 5, '2010-09-26 22:36:57', 2, '2011-03-02 12:13:50', 2, 3);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (11, 22, 2, 'template', 5, '2010-09-26 22:37:25', 2, '2011-03-02 12:13:50', 2, 0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (16, 22, 2, 'field', 5, '2010-09-26 22:37:33', 2, '2011-03-02 12:13:50', 2, 2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (21, 2, 2, 'module', 5, '2010-09-27 22:49:37', 2, '2011-03-02 12:13:50', 2, 2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (22, 2, 2, 'setup', 5, '2010-10-18 22:43:35', 2, '2011-03-02 12:13:50', 2, 1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (23, 2, 2, 'login', 1027, '2010-10-21 12:56:53', 2, '2011-03-02 12:13:50', 2, 5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (24, 2, 2, 'logout', 1027, '2010-10-21 12:56:42', 2, '2011-03-02 12:13:50', 2, 4);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (25, 5, 2, 'role', 7, '2010-09-26 22:38:22', 2, '2011-03-02 12:13:50', 2, 10);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (26, 5, 2, 'user', 5, '2010-09-26 22:38:07', 2, '2011-03-02 12:13:50', 2, 1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (27, 1, 29, 'http404', 1027, '2010-11-30 11:17:07', 2, '2011-03-02 12:13:50', 3, 4);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5733, 3, 2, 'image', 1, '2010-10-12 20:23:20', 2, '2011-03-02 12:13:50', 2, 7);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5740, 1, 29, 'about', 1, '2010-11-30 10:52:26', 2, '2011-03-02 12:13:50', 2, 0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5743, 5740, 29, 'what', 1, '2010-11-30 13:28:29', 2, '2011-03-02 12:13:50', 2, 0);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5724, 1, 26, 'search', 1025, '2010-11-30 11:17:07', 2, '2011-03-02 12:13:50', 2, 3);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5722, 3, 2, 'search', 5, '2010-09-26 22:37:06', 2, '2011-03-02 12:13:50', 2, 5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5731, 3, 2, 'link', 1, '2010-09-30 21:05:31', 2, '2011-03-02 12:13:50', 2, 6);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5729, 3, 2, 'trash', 7, '2010-09-27 21:39:55', 2, '2011-03-02 12:13:50', 2, 5);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5748, 1, 29, 'templates', 1, '2010-11-30 10:56:15', 2, '2011-03-02 12:13:50', 2, 1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5765, 1, 34, 'site-map', 1, '2010-11-30 11:18:37', 2, '2011-03-02 12:13:50', 2, 2);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5763, 5740, 29, 'background', 1, '2010-11-30 09:45:28', 2, '2011-03-02 12:13:50', 2, 1);
INSERT INTO `pages` (`id`, `parent_id`, `templates_id`, `name`, `status`, `modified`, `modified_users_id`, `created`, `created_users_id`, `sort`) VALUES (5764, 7, 29, '5764_tertiary-page-test', 8193, '2010-11-29 18:17:38', 2, '2011-03-02 12:13:50', 2, 0);
UPDATE `pages` SET created=NOW(), modified=NOW(); 
DROP TABLE IF EXISTS `pages_parents`;
CREATE TABLE IF NOT EXISTS `pages_parents` (
  `pages_id` int(10) unsigned NOT NULL,
  `parents_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pages_id`,`parents_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `pages_roles`;
CREATE TABLE IF NOT EXISTS `pages_roles` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `roles_id` int(10) unsigned NOT NULL default '0',
  `pages_id` int(10) unsigned NOT NULL default '0',
  `action` enum('+','-') NOT NULL default '+',
  PRIMARY KEY  (`id`),
  KEY `pages_id` (`pages_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;
INSERT INTO `pages_roles` (`id`, `roles_id`, `pages_id`, `action`) VALUES (14, 2, 1, '+');
INSERT INTO `pages_roles` (`id`, `roles_id`, `pages_id`, `action`) VALUES (3, 1, 7, '-');
INSERT INTO `pages_roles` (`id`, `roles_id`, `pages_id`, `action`) VALUES (4, 1, 2, '-');
INSERT INTO `pages_roles` (`id`, `roles_id`, `pages_id`, `action`) VALUES (5, 1, 23, '+');
INSERT INTO `pages_roles` (`id`, `roles_id`, `pages_id`, `action`) VALUES (13, 1, 1, '+');
DROP TABLE IF EXISTS `pages_sortfields`;
CREATE TABLE IF NOT EXISTS `pages_sortfields` (
  `pages_id` int(10) unsigned NOT NULL default '0',
  `sortfield` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`pages_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `summary` text NOT NULL,
  `modules_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `modules_id` (`modules_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=59 ;
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (50, 'ProcessPageSortMove', 'Move a page to another parent', 14);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (45, 'ProcessPageEditDelete', 'Delete pages', 7);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (49, 'ProcessPageSort', 'Move pages within the same parent (i.e. sort pages)', 14);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (48, 'ProcessPageList', 'List pages - Recommended for all roles that have Admin access', 12);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (11, 'ProcessRole', 'Edit/add/delete user roles - Recommended for Superuser only', 68);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (44, 'ProcessPageEdit', 'Edit pages', 7);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (15, 'ProcessTemplate', 'Edit/add/delete templates - Recommended for Superuser only', 47);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (17, 'ProcessModule', 'Edit/install/uninstall modules - Recommended for Superuser only', 50);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (18, 'ProcessField', 'Edit/add/delete fields - Recommended only for Superuser', 48);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (22, 'ProcessLogin', 'Login capability - Warning, do not remove this from the guest role as it will prevent any login (including yours)', 10);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (23, 'ProcessLogout', 'Logout capability - Recommended for all roles except Guest', 11);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (24, 'ProcessPageAdd', 'Add pages - As a prerequisite, user must also be able to edit pages ', 17);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (27, 'ProcessUser', 'Edit/add/delete users - Recommended for Superuser only', 66);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (31, 'ProcessList', 'List processes for generating navigation - Recommended for all roles with admin access', 76);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (37, 'ProcessPageView', 'View pages - Recommended for Guest role only (and it will be inherited by all other roles)', 83);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (52, 'ProcessHome', 'Admin homepage - Recommended for all roles with admin access', 87);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (54, 'ProcessPageSearch', 'Search pages within Admin - Recommended for all roles except Guest', 104);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (55, 'ProcessPageTrash', 'Empty the page trash - Recommended for Superuser only', 109);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (57, 'ProcessPageEditLink', '', 121);
INSERT INTO `permissions` (`id`, `name`, `summary`, `modules_id`) VALUES (58, 'ProcessPageEditImageSelect', '', 129);
DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;
INSERT INTO `roles` (`id`, `name`) VALUES (1, 'guest');
INSERT INTO `roles` (`id`, `name`) VALUES (2, 'superuser');
INSERT INTO `roles` (`id`, `name`) VALUES (3, 'owner');
DROP TABLE IF EXISTS `roles_permissions`;
CREATE TABLE IF NOT EXISTS `roles_permissions` (
  `permissions_id` int(10) unsigned NOT NULL default '0',
  `roles_id` int(10) unsigned NOT NULL default '0',
  `sort` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`permissions_id`,`roles_id`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (55, 2, 17);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (54, 2, 16);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (52, 2, 15);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (27, 2, 14);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (15, 2, 13);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (11, 2, 12);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (37, 2, 11);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (50, 2, 10);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (49, 2, 9);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (48, 2, 8);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (45, 2, 7);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (44, 2, 6);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (24, 2, 5);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (17, 2, 4);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (23, 2, 3);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (22, 2, 2);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (37, 1, 0);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (31, 2, 1);
INSERT INTO `roles_permissions` (`permissions_id`, `roles_id`, `sort`) VALUES (18, 2, 0);
DROP TABLE IF EXISTS `session_login_throttle`;
CREATE TABLE IF NOT EXISTS `session_login_throttle` (
  `name` varchar(128) NOT NULL,
  `attempts` int(10) unsigned NOT NULL default '0',
  `last_attempt` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS `templates`;
CREATE TABLE IF NOT EXISTS `templates` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `fieldgroups_id` int(10) unsigned NOT NULL default '0',
  `cache_time` mediumint(9) NOT NULL default '0',
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `fieldgroups_id` (`fieldgroups_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `cache_time`, `data`) VALUES (2, 'admin', 2, 0, '{"allowPageNum":0,"https":0,"redirectLogin":1}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `cache_time`, `data`) VALUES (29, 'page', 83, 0, '{"allowPageNum":0,"redirectLogin":0,"https":0}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `cache_time`, `data`) VALUES (26, 'search', 80, 0, '{"allowPageNum":1}');
INSERT INTO `templates` (`id`, `name`, `fieldgroups_id`, `cache_time`, `data`) VALUES (34, 'sitemap', 88, 0, '{"childrenTemplatesID":-1,"allowPageNum":0,"redirectLogin":0,"https":0}');
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL default '',
  `pass` varchar(40) NOT NULL,
  `salt` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
INSERT INTO `users` (`id`, `name`, `pass`, `salt`) VALUES (1, 'guest', '', '');
DROP TABLE IF EXISTS `users_roles`;
CREATE TABLE IF NOT EXISTS `users_roles` (
  `users_id` int(10) unsigned NOT NULL default '0',
  `roles_id` int(10) unsigned NOT NULL default '0',
  `sort` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`users_id`,`roles_id`),
  KEY `sort` (`sort`),
  KEY `roles_id` (`roles_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO `users_roles` (`users_id`, `roles_id`, `sort`) VALUES (1, 1, 0);

