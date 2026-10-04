=== Campussian Core ===
Contributors: whycodebd
Tags: roles, user-roles, access-control, education, school
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

School role management, access control and the content types used by the Campussian education theme.

== Description ==

Campussian Core is the companion plugin for the **Campussian** education theme. It provides the school-specific roles, the access-control rules and the content types that the theme's homepage sections display.

= Roles =

The plugin adds six school roles with hand-tuned capability sets:

* **System Admin** – full site control (plugins, themes, users, settings).
* **School Administrator** – manages all school content and users.
* **Principal** – manages all school content and users.
* **Teacher** – manages Notices and Students.
* **Guardian** – front-end portal access only.
* **Student** – front-end portal access only.

On activation existing users are moved onto the closest matching school role. **WordPress's own roles are never deleted**, so deactivating or removing the plugin cannot break your site or other plugins.

= Access control =

* Students and Guardians are redirected away from `/wp-admin/` to the front-end portal.
* The admin bar is hidden for those roles.
* Logins are routed to the portal Dashboard.
* The admin menu is trimmed for School Administrators, Principals and Teachers.
* System Admin accounts are protected from being edited by other roles.

= Content types =

Registers Notices, Events, News, Gallery, Facilities, Teachers, Alumni, Admissions Applications, Students and Campussian Settings, plus the Notice Categories taxonomy.

= Compatible with =

Any theme. It is built for the Campussian theme but works standalone, and it never modifies core roles or core content types.

== Installation ==

1. Go to **Plugins > Add New** in your WordPress admin.
2. Search for **Campussian Core** and click **Install Now**, or upload the zip with **Upload Plugin**.
3. Click **Activate**.
4. Optionally install the **Campussian** theme to use the matching templates.

== Frequently Asked Questions ==

= Does it delete the default WordPress roles? =

No. The administrator, editor, author, contributor and subscriber roles are left exactly as they are. The plugin only adds its own six school roles.

= What happens to my users if I deactivate the plugin? =

On deactivation every user is moved back onto a standard WordPress role, and the role they had before is remembered so it can be restored if you reactivate. Deleting the plugin runs the same clean-up and removes the school roles.

= Can I use this with a different theme? =

Yes. The roles and access-control rules are theme-independent. Only the page templates for the portal Dashboard and Login come from the Campussian theme.

= Is any data sent anywhere? =

No. The plugin makes no external requests, sets no cookies of its own and does not track users.

== Screenshots ==

1. The school roles listed under Users > Add New.
2. The portal Dashboard rendered on the front end.

== Changelog ==

= 1.0.5 =
* Roles are now rebuilt only on install or version upgrade instead of on every page load.
* WordPress core roles are no longer removed.
* Added uninstall clean-up that restores users to standard roles.
* Capability filtering now only affects the plugin's own roles.
* Added the school content types (moved out of the theme).

= 1.0.0 =
* Initial release: school roles, access control, login routing and portal pages.

== Upgrade Notice ==

= 1.0.5 =
Safer role handling, plus the school content types are now registered by this plugin. Update before updating the Campussian theme.
