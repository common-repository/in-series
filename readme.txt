=== In Series ===
Contributors: quandary
Donate link: http://remstate.com/donate/
Tags: links, navigation, post, posts, series
Requires at least: 1.5.2
Tested up to: 2.3.2
Stable tag: 3.0.12

Gives authors an easy way to connect posts together as a series.

== Description ==

In Series provides a way for you to manage stringing together posts in a series.
You can add and remove individual posts from series, and reorder posts within a
series, all from the post writing screen. The plugin also allows you to
customize how series information (like tables of contents and next/previous
links) are rendered across your site. Best of all, In Series does not require
any template hacking, or knowledge of PHP -- it will work just fine right out
of the box.

== Installation ==

1. It is *highly* recommended that you back up your WordPress database.
1. Deactivate and delete any existing version of In Series you have installed
1. Unzip in-series-X_Y_Z.zip (where X, Y and Z represent the version of In
Series that you downloaded)
1. Upload the in-series directory to your wp-content/plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Alter the display formatting to your taste in the 'Series' sub-menu (under
'Options') in WordPress.
1. Create, modify, and delete posts as desired.

== Frequently Asked Questions ==

The FAQ list is maintained separately, so that it can be updated more frequently
than once each release.

[View the FAQ](http://remstate.com/projects/in-series/faq/ "Frequently Asked Questions about In Series")

== Screenshots ==

The screenshots are maintained separately, to vastly reduce the size of the file
you have to download, and so that new screenshots can be added more frequently
than once each release.

[View the screenshots](http://remstate.com/projects/in-series/screenshots/ "Screenshots of In Series")

== TODO ==

Need write ups for...

* The new widgets (ToC and Series List so far)
* The new configuration screen
* The <!--series-*--> tags
* First/last in series series links
* Single/multi view layouts (adv. config)
* "New" series -> existing on match
* "Active" link behaviors for first/prev/next/last

== Display Formatting ==

In order to provide maximum flexibility, In Series formats posts based on a set
of special formatting strings. These are accessed through the 'Series' sub-menu
under the 'Options' menu in WordPress. You must have the 'manage_options'
capability (WordPress 2.0 and later) or have at least user level 8 (prior to
WordPress 2.0) to alter these options. 

For the time being, the formats are site-wide, and appear on both single- and
multi-post pages. This will change in the future, but for the time being, make
sure that the formats you select look acceptable to you throughout your site.

The key component to all of these fields are a set of special tokens. These are
replaced with appropriate values, based on context. The complete list of tokens
is as follows:

* %content -- text of the post.
* %entries -- complete list of recurring items, e.g., the entries in a table of
contents.
* %next -- link to the next post in the series (if one exists).
* %prev -- link to the previous post in the series (if one exists).
* %series -- title of the series.
* %title -- title of the post. Which post is based on context.
* %toc -- a table of contents enumerating all the published posts in a series.
* %url -- an unadorned URL. What this URL points to is based on context.

It is important to note that the above tokens may expand differently (or not at
all) if used inside of an HTML tag. A very common scenario that illustrates this
would be <a href='%url' title='%title'>%title</a>. The first %title will have
any HTML stripped out of it, and will have the remaining special characters
(like "'" and "<") escaped. The second %title, though, will not have any content
stripped or escaped. The special expansion rules apply as described in the list
below:

* Not expanded between < and >
    * %content
    * %entries
    * %next
    * %prev
    * %toc

* HTML-stripped and escaped between < and >
    * %series
    * %title

* Expanded the same everywhere
    * %url

= Post layout =

This option controls the manner in which each post is generated. It is important
to have the "%content" token (without the quotes) appear exactly once in this
field. More than once, and the post's contents will show up twice; if "%content"
isn't present at all, then your posts will not have any content. In Series will
not prevent you from making this mistake, so please double-check!

Valid tokens for this field are:

* %content
* %next
* %prev
* %toc

= Next Link =

This option controls how the %next token is expanded.

Valid tokens for this field are:

* %series
* %title -- the title of the next post in the series
* %url -- a URL pointing to the next post in the series

= Previous Link =

This option controls how the %prev token is expanded.

Valid tokens for this field are:

* %series
* %title -- the title of the previous post in the series
* %url -- a URL pointing to the previous post in the series

= Table of Contents Layout =

This option controls how the %toc token is expanded.

Valid tokens for this field are:

* %entries
* %series
* %title -- the title of the post that the Table of Contents is being generated
in

= Entry Link and Active Entry Link =

These fields control how the %entries token is expanded. The entry link field is
used for most of the entries. However, if the post that the %entry token would
ultimately be a part of is the same post that an entry would refer to, the
active entry link field is used instead. This allows you to prevent having a
table of contents with a link pointing to the page that you're already on (for
example).

These fields are present for both the table of contents, and the series list.

Valid tokens for these fields are:

* %series
* %title -- this value is changed for each entry in the list, representing the
title of each post that is part of the group (one at a time).
* %url -- this value is changed for each entry in the list, representing the
title of each post that is part of the group (one at a time).

= Series List = 

As a special note, the series list is not automatically inserted. Currently,
it can be accessed through a template tag only. The use of the series list is,
therefore, unsupported for the time being.

== Misc Options ==

Aside from the formatting strings, there are some other options that can be
controlled from the 'Series' menu.

= Insert <link> Tags =

This option, when enabled, will insert <link rel="prev" href="..." /> and <link
rel="next" href="..." /> (with appropriate hrefs) in each single-view page that
displays a post in a series. Some browsers can use this information to more
explicitly understand that the page is part of a series, and is intended to
improve accessibility.

== Manipulating Series ==

In Series tries to make it as easy as possible for authors to create and change
series. Series manipulation is handled with post editing; to change a post's
relationship with a series, start by editing the post.

= HTML Comment-based manipulation =

You can use HTML comments to add a post to a series. This is useful if, for
example, you make a large number of blog posts via e-mail, or through an
interface other than the web interface. You can use the following comment tags
to control how In Series will treat a new post:

* <!--Series-name: Your Series Name -->
* <!--Series-order: start -->

The series name tag will control which series the post is added to. If the
series does not exist, it will be created. The series order tag will control
where in the series the post is placed. If no tag is present, In Series will
default to placing the post at the end of the series.

All In Series comment tags are removed after processing -- they do not appear
in the final post.

= Adding to a New Series =

1. If you intend to save the post to have an author that is not the same as the
user you are logged in as, save the post first before making any series changes.
1. If the post is already in a series, you will need to remove it from that
series before you can add it to a new one (see Removing from a Series).
1. From the In Series sidebar, enter the name of the new series you wish to
create in the empty text box. Ensure that you have not already created a series
with the same name (In Series will not permit you to have two series with the
same name), and that the drop-down box is set to "--- New Series ---".
1. Save the post.

If you enter the name of a series that already exists, In Series will assume
that you wish to add the post to the already-existing series (as if you had
selected that series from the drop-down menu).

= Adding to an Existing Series =

1. If you intend to save the post to have an author that is not the same as the
user you are logged in as, save the post first before making any series changes.
1. If the post is already in a series, you will need to remove it from that
series before you can add it to a new one (see Removing from a Series).
1. From the In Series sidebar, select the name of the series you wish to add the
post to from the drop-down menu.
1. Select the "start" radio button if you would like the post to be added as the
first post in the series; otherwise, leave the "end" radio button selected.
1. Save the post.

= Removing from a Series =

Note that deleting a post will automatically remove it from any series that it
is in.

1. The In Series sidebar should contain the name of the series that the post is
a part of, followed by a drop-down menu. Select "--- Remove ---" from the
drop-down menu.
1. Save the post.

= Reorder in a Series =

1. The In Series sidebar should contain the name of the series that the post is
a part of, followed by a drop-down menu. From that menu, select the name of the
post that you would like the current post to come *after*. If you would like the
post to be the first in the series, select "--- First ---".
1. Save the post.
