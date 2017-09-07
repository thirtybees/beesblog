
-- -----------------------------------------------------
-- MAJ Table `mydb`.`blog_category`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tb_bees_blog_category`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_category` (
  `id_bees_blog_category`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_parent`       INT(11) NOT NULL,
  `position`        INT(11) NULL,
  `active`          INT(11) NULL,
  `date_add`        DATETIME NULL,
  `date_upd`        DATETIME NULL,
  PRIMARY KEY (`id_bees_blog_category`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `tb_bees_blog_category_lang`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_category_lang` (
  `id_bees_blog_category`   INT(10) NOT NULL AUTO_INCREMENT,
  `id_lang`                 INT(10) NOT NULL,
  `title`                   VARCHAR(256)  NULL,
  `description`             VARCHAR(512)  NULL,
  `link_rewrite`            VARCHAR(256) NULL,
  PRIMARY KEY (`id_bees_blog_category`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tb_bees_blog_category_lang` (`id_bees_blog_category`, `id_lang`, `title`, `description`, `link_rewrite`) VALUES
(5, 1, 'Social Acquisition', 'Social acquisition from beginner to hero', 'social-acquisition');
INSERT INTO `tb_bees_blog_category` (`id_bees_blog_category`, `id_parent`, `position`, `active`, `date_add`, `date_upd`) VALUES
(5, 0, 1, 1, '2017-09-06 05:45:40', '2017-09-06 05:45:40');

--
-- -----------------------------------------------------
-- MAJ Table `mydb`.`blog_category`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tb_bees_blog_post`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_post` (
  `id_bees_blog_post`  INT(10) NOT NULL AUTO_INCREMENT,
  `active`             INT(10) NULL,
  `comments_enabled`   INT(10) NULL,
  `date_add`           DATETIME NULL,
  `date_upd`           DATETIME NULL,
  `published`          DATETIME NULL,
  `id_category`        INT(10) NULL,
  `id_employee`        TEXT  NULL,
  `image`              VARCHAR(128) NULL,
  `position`           INT(10) NULL,
  `post_type`          VARCHAR(255) NULL,
  `viewed`             INT(20) NULL,
  PRIMARY KEY (`id_bees_blog_post`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `tb_bees_blog_post_lang`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_post_lang` (
  `id_bees_blog_post`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_lang`            INT(10) NOT NULL,
  `title`              VARCHAR(255) NULL,
  `content`            TEXT NULL,
  `link_rewrite`       VARCHAR(255) NULL,
  `lang_active`        INT(10) NULL,
  PRIMARY KEY (`id_bees_blog_post`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `tb_bees_blog_post_shop`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_post_shop` (
  `id_bees_blog_post`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_shop`            INT(10) NOT NULL,
  PRIMARY KEY (`id_bees_blog_post`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO `tb_bees_blog_post` (`id_bees_blog_post`, `active`, `comments_enabled`, `date_add`, `date_upd`, `published`, `id_category`, `id_employee`, `image`, `position`, `post_type`, `viewed`) VALUES
(1, 1, 1, '2017-09-06 05:51:07', '2017-09-06 05:51:07', '2017-09-04 00:00:00', 1, '1', '', 1, '0', 1),
(2, 1, 1, '2017-09-06 05:52:14', '2017-09-06 07:21:03', '2017-09-04 00:00:00', 1, '1', '', 1, '0', 4),
(3, 1, 1, '2017-09-06 05:53:14', '2017-09-06 07:20:03', '2017-09-01 00:00:00', 1, '1', '', 1, '0', 1),
(4, 1, 1, '2017-09-07 05:11:14', '2017-09-07 05:11:14', '2017-09-07 05:11:14', 5, '1', '', 1, '0', 0);

INSERT INTO `tb_bees_blog_post_shop` (`id_bees_blog_post`, `id_shop`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1);

INSERT INTO `tb_bees_blog_post_lang` (`id_bees_blog_post`, `id_lang`, `title`, `content`, `link_rewrite`, `lang_active`) VALUES
(1, 1, 'How to Create a Social Media Content Calendar in 4 Simple Steps ', '<p>Do you know what you’re going to post on Facebook tomorrow? How about next week?</p>\r\n<p>Planning out your business’ content in advance is a key part of building a social media strategy that actually works. Unfortunately, for many business owners, content planning often gets pushed to the backburner.</p>\r\n<p>It’s not surprising either—as an entrepreneur, your time is usually spent handling tasks that need to be tackled immediately: <a target="_blank" href="https://www.shopify.com/blog/14069585-the-beginners-guide-to-ecommerce-shipping-and-fulfillment">Shipping out orders</a>, <a target="_blank" href="https://www.shopify.com/blog/16817540-5-ways-to-take-charge-of-your-ecommerce-customer-service">putting out customer service fires</a>, and keeping your business one step ahead of the competition. Your days are busy and you don’t always have time to think about tweets and Pins.</p>\r\n<p>However, if you dedicate just a few hours at the start of every month to creating a content calendar in advance for your business, you’ll be able to grow your social presence without having to constantly worry about what to share next.</p>\r\n<p>To help you get organized, we’ve put together a step-by-step guide to building a content calendar that engages, delights, and grows your audience.</p>\r\n<h2>Why Do You Need a Content Calendar?</h2>\r\n<p>A content calendars is exactly what it sounds like: A calendar featuring all of your upcoming social media posts.</p>\r\n<p>For many business owners, the idea of planning out an entire month of content might seem a little unnecessary. Maybe you only occasionally find yourself rushing to find an article to share on Facebook or trying to think up a clever holiday greeting to tweet out. So, is planning that far in advance really worth it?</p>\r\n<p>The truth is that a content calendar is so much more than just an agenda or day planner for your social profiles. Here are just a few of the reasons why a proper social media content calendar will help you build better marketing campaigns:</p>\r\n<h3>Content Calendars Are Strategic</h3>\r\n<p>A content calendar is the foundation of every successful <a target="_blank" href="https://www.shopify.com/blog/topics/social-media-marketing">social media marketing</a> strategy.</p>\r\n<p>If you aren’t planning your social media strategy ahead of time, then you’re just doing things at random with no sense of the bigger picture. Content calendars help you take a strategic approach to your social media presence, so that you get a better idea of how your actions actually fit into your overarching business goals.</p>\r\n<h2>How to Build a Content Calendar for Your Online Store</h2>\r\n<p>Building a content calendar for an entire month in one shot sounds like a daunting task, but it’s much easier if you know which tools to use.</p>\r\n<p>So, let’s take a look at each step of the process along with the resources you’ll need to get the job done.</p>\r\n<h3>1. Creating a Framework for Your Content Calendar</h3>\r\n<p>In order to build a content calendar, it’s a good idea to create a blank calendar template to help you visualize what your monthly content output will look like.</p>\r\n<p>To build a template, it’s better to opt for versatile options like <a rel="nofollow noopener noreferrer" target="_blank" href="https://calendar.google.com/">Google Calendar</a> or <a rel="nofollow noopener noreferrer" target="_blank" href="https://trello.com/">Trello</a>. With both of these tools, you’ll be able to create collaborative content calendars that include details like the timing of posts and labels for content categories.</p>\r\n<p><strong>Google Calendar</strong></p>\r\n<p>If you already have a Google account, then Google Calendar is a natural first choice for building out your content calendar. While it wasn’t originally designed to plan out content, you can easily use its features to organize your social posts for the next month.</p>\r\n<p>To get started, you’ll need to create a new calendar and invite your teammates to it so that you can start collaborating on content. Dropping in new posts will be as simple as adding a new event to your calendar, using the Event Time field to plug in your post time.</p>\r\n<h3>2. Finding out Which Types of Content Your Audience Enjoys</h3>\r\n<p>Once you’ve decided how you’re going to create a calendar outline, you’ll need to figure out which types of content resonate with your audience. Determining this is as easy as finding out which pieces of content are already generating the most engagement for your brand and helping you grow your audience on social media.</p>\r\n<p>To figure out which types of content you should be focusing on, you need to dive into your analytics for your different social profiles and find the common traits that connect your best performing posts.</p>\r\n<p>Here are some resources on the analytics features of some of the biggest social media platforms:</p>\r\n<ul>\r\n<li><a rel="nofollow noopener noreferrer" target="_blank" href="https://www.facebook.com/help/336893449723054/">Facebook Insights</a></li>\r\n<li><a rel="nofollow noopener noreferrer" target="_blank" href="https://analytics.twitter.com/">Twitter Analytics</a></li>\r\n<li><a rel="nofollow noopener noreferrer" target="_blank" href="https://analytics.pinterest.com/">Pinterest Analytics</a></li>\r\n<li><a rel="nofollow noopener noreferrer" target="_blank" href="https://business.instagram.com/">Instagram for Business</a></li>\r\n</ul>\r\n<p>After looking through the analytics for each of your platforms, you should be able to determine which types of content are most popular with your audience. Look for the posts with the highest levels of engagement and the most clickthroughs.</p>\r\n<p>Once you’ve identified your best pieces of content, segment your calendar to create a diverse output of content.</p>\r\n<p>An easy way to make sure that your calendar contains an appropriate mix of content is by dedicating specific days of the week to certain types of posts. For instance, if you’re an athletics company, maybe every Tuesday you share a motivational quote and every Thursday you share a new smoothie recipe. Get creative and don’t be afraid to test out and refine your ideas!</p>', 'how-to-create-a-social-media-content-calendar-in-4-simple-steps-', 0),
(2, 1, '9 Social Media Marketing Pros Share Their Best Advice for Today\'s Entrepreneurs ', '<p>en years ago, social media marketing was a lot different.</p>\r\n<p>Competing online simply meant establishing a compelling presence that would spread organically. Choosing your channels involved only a handful of popular options. Creating “media” mostly meant writing posts and designing images.</p>\r\n<p>But today, there are <a rel="nofollow noopener noreferrer" target="_blank" href="http://www.statista.com/topics/1164/social-networks/">roughly 2.34 billion social network users</a> around the world—a third of the global population.</p>\r\n<p>New players are crowding the scene as it gets harder to tell the difference between Snapchat and Instagram. Brands are now paying just to compete for a few seconds of their audience’s attention. And the content they\'re pushing now includes live video and augmented reality (e.g. Snapchat\'s video lenses).</p>\r\n<blockquote><img alt="rand fishkin social media marketing" src="https://cdn.shopify.com/s/files/1/0070/7032/files/quote_tweet_template_rand.jpg?17725230103579571501" /></blockquote>\r\n<h3><strong>What is the biggest change/challenge you foresee in the near future of social media marketing?</strong></h3>\r\n<p>Standing out from the crowd, amidst an increasingly noisy, competitive field. People only have so much time in their day to consume media and content, and social channels are fast becoming overwhelmed.</p>\r\n<p>The deficit of attention means content creators and social marketers will need to be massively more unique, more valuable, and earn more loyalty from their audiences in order to maintain or grow their presences.</p>\r\n<h3><a rel="nofollow noopener noreferrer" target="_blank" href="http://trackmaven.com/">Kara Burney</a>, Director of Content at Track Maven</h3>\r\n<p><img alt="kara burney social media marketing" src="https://cdn.shopify.com/s/files/1/0070/7032/files/quote_tweet_template_kara.jpg?18254288842411860513" /></p>\r\n<h3><strong>What is the biggest change/challenge you foresee in the near future of social media marketing?</strong></h3>\r\n<p>The near future of social media marketing is all about creating immersive user experiences.</p>\r\n<p>Businesses have gotten really good at distributing content on social media. But most are still not very good at creating content worth distributing in the first place. As the bar is raised for immersive content, broadcasting the same static content across each and every social channel won’t cut it anymore. Businesses have to figure out how to plan and invest in a channel-specific social media strategy.</p>\r\n<h3><a rel="nofollow noopener noreferrer" target="_blank" href="http://pegfitzpatrick.com/about/">Peg Fitzpatrick</a>, Author & Social Media Strategist</h3>\r\n<p><img alt="peg fitzpatrick social media marketing" src="https://cdn.shopify.com/s/files/1/0070/7032/files/quote_tweet_template_peg.jpg?9198472508700760643" /></p>\r\n<h3><strong>What is the biggest change/challenge you foresee in the near future of social media marketing?</strong></h3>\r\n<p>Keeping up with all the crazy changes. Facebook is getting more complicated as time goes on and I think this will make it harder for entrepreneurs and small businesses to keep up with managing their social media platforms wisely and effectively.</p>\r\n<h3><a rel="nofollow noopener noreferrer" target="_blank" href="http://casiestewart.com/">Casie Stewart</a>, Lifestyle Blogger</h3>\r\n<p><img alt="casie stewart social media marketing" src="https://cdn.shopify.com/s/files/1/0070/7032/files/quote_tweet_template_casie.jpg?1624409483565990451" /></p>\r\n<h3><strong>What is the biggest change/challenge you foresee in the near future of social media marketing?</strong></h3>\r\n<p>Audiences and platforms are constantly changing so knowing what content to put where can be a challenge. You want to go where the people are, but their habits are changing too.</p>\r\n<p>Should your video content live on Facebook, YouTube, Twitter, Instagram, IG Stories, Snapchat, Vine, Periscope? Platforms are moving organisms, adding new features all the time. If you wait too long to put a piece of marketing content together, you can miss the window of opportunity for something to be ‘cool’.</p>\r\n<p>I was part of 12seconds.tv, a short video platform in 2011–12 before it was bought by AOL. After that, Viddy came along and was the “Instagram for Video”, but when Instagram added video, they were done.</p>\r\n<p>Last month everyone was crazy about Snapchat and then Instagram Stories happened, and now Twitter Moments are rolling out. In one app update your whole strategy can be thrown off, you need to be agile, and ready for curveballs.</p>\r\n<p>I also think we’re coming up to a shift in influencer marketing. It grew so fast, and now it’s like the wild wild west out there!</p>\r\n<h3><a rel="nofollow noopener noreferrer" target="_blank" href="http://unthinkable.fm/">Jay Acunzo</a>, Host of Unthinkable</h3>\r\n<p><img alt="jay acunzo social media marketing" src="https://cdn.shopify.com/s/files/1/0070/7032/files/quote_tweet_template_jay.jpg?14961673042031011179" /></p>\r\n<h3><strong>What is the biggest change/challenge you foresee in the near future of social media marketing?</strong></h3>\r\n<p>Great content is always the biggest challenge to a great social media presence.</p>\r\n<p>Corporations—especially large ones—are historically used to interchangeable parts. This is how you “scale” a team – you iron out processes and train people to be as interchangeable as possible. However, when the name of the game is creating content that stands out and deeply resonates (and that game is quickly becoming multimedia rather than text-centric), finding and retaining enough Creative Talent with a capital T is going to be THE differentiating factor.</p>\r\n<p>This isn’t about programmatic. This isn’t about technology. This isn’t about quick-hit conversion-centric marketing. First and foremost, this is about building things that are meaningful and that others actually love in the world. You need really great creators for that.</p>\r\n<h3><a rel="nofollow noopener noreferrer" target="_blank" href="http://neilpatel.com/">Neil Patel</a>, Co-Founder of Crazy Egg & KISSmetrics</h3>\r\n<p><img class=" lazyloaded" data-src="//cdn.shopify.com/s/files/1/0070/7032/files/quote_tweet_template_neilpatel.jpg?9949587126512904988" alt="neil patel shopify" src="https://cdn.shopify.com/s/files/1/0070/7032/files/quote_tweet_template_neilpatel.jpg?9949587126512904988" /></p>\r\n<h3><strong>What is the biggest change/challenge you foresee in the near future of social media marketing?</strong></h3>\r\n<p>Social networks have been adjusting their algorithms and making it harder for companies to do well “organically”. Essentially it is turning into a “pay to play” game which is going to take a lot of companies out.</p>\r\n<h3></h3>\r\n<h2><a rel="nofollow noopener noreferrer" target="_blank" href="https://www.buffer.com/">Brian Peters</a>, Social Media at Buffer</h2>\r\n<p></p>', '9-social-media-marketing-pros-share-their-best-advice-for-today-s-entrepreneurs-', 0),
(3, 1, '5 High-Impact Strategies for Getting More Traffic ', '<p>“How do I drive more traffic to my online store?”</p>\r\n<p>That thought crosses the mind of every ecommerce entrepreneur at some point.</p>\r\n<p>Maybe you’ve just sunk time and effort into painstakingly setting up your store, only to open up shop and wonder where your sales are. Maybe you’ve seen steady growth over the past 6 months, but just hit a plateau. Or maybe you’ve built a million dollar business and now you’re setting your sights on your next big goal.</p>\r\n<p>Whether you’re trying to attract your first customer or your 10,000th customer, generating more traffic to your online store is a crucial part of growing your business. <a target="_blank" href="https://www.shopify.com/blog/120261189-conversion-rate-optimization">If your site is properly optimized for conversions</a>, getting a jump in traffic could mean more customers and more sales.</p>\r\n<p>To help you increase traffic for your online store, we’ve put together a list of 5 proven, high impact tactics for driving more traffic to your online store.</p>\r\n<h2>1. Run Paid Social Media Ad Campaigns</h2>\r\n<p>To increase website traffic for your online store, you need to be able to get your business in front of your ideal customers. With paid social media ads, you can create highly targeted campaigns that serve tailor-made ads to the customers who are most likely to click through and purchase your products.</p>\r\n<p>If you’re thinking about running paid social media ads, here are some platforms you should consider:</p>\r\n<h2>2. Use SEO to Increase Your Store’s Discoverability</h2>\r\n<p>Can your customers actually find your store online?</p>\r\n<p>When customers search for your products online, you want your store to be one of the top results for that search, especially since <a rel="nofollow noopener noreferrer" target="_blank" href="https://www.socialfresh.com/marketing-statistics-every-cmo-should-know-in-2014/">⅓ of all clicks go to the first organic result on Google</a>. That prized top position is a key ingredient for generating sustained, qualified website traffic for your online store.</p>\r\n<h2>3. Reach New Audiences with Influencer Marketing</h2>\r\n<p>Influencer marketing is the pcess of building relationships with influencers to get your online store in front of new audiences.</p>\r\n<p>With influencer marketing, you can harness the creativity and reach of relevant influencers in your industry while leveraging the trust that they’ve already formed with their audiences.</p>\r\n<h2>4. Get More Traffic, Get More Customers</h2>\r\n<p>With these tactics in your ecommerce marketing toolkit, you should now be able to generate more website traffic for your online store.</p>\r\n<p>More traffic to your online store means more opportunities to turn those casual shoppers into paying customers. Once you’ve increased your traffic, consider trying out <a target="_blank" href="https://www.shopify.com/blog/120261189-conversion-rate-optimization">Conversion Rate Optimization</a> as a next step.</p>\r\n<p>Have any questions about how to increase website traffic for your online store? Share them in the comments below!</p>', '5-high-impact-strategies-for-getting-more-traffic-', 0);

-- -----------------------------------------------------
-- MAJ Table `mydb`.`tb_bees_blog_image_type`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tb_bees_blog_image_type`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_image_type` (
  `id_bees_blog_image_type`  INT(10) NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(255) NULL,
  `width`             INT(10) NULL,
  `height`            INT(10) NULL,
  `posts`             INT(10) NULL,
  `categories`        INT(10) NULL,
  PRIMARY KEY (`id_bees_blog_image_type`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `tb_bees_blog_image_type` (`id_bees_blog_image_type`, `name`, `width`, `height`, `posts`, `categories`) VALUES (NULL, 'aaa', '650', '200', '1', '0');

DROP TABLE IF EXISTS `tb_bees_blog_image_type_shop`;
CREATE TABLE IF NOT EXISTS `tb_bees_blog_image_type_shop` (
  `id_bees_blog_image_type`  INT(10) NOT NULL AUTO_INCREMENT,
  `id_shop`                  INT(10) NOT NULL,
  PRIMARY KEY (`id_bees_blog_image_type`))
ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
