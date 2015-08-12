## wp-cli-schema

Adds extra commands to [wp-cli](http://wp-cli.org/) to do schema upgrades for
Wordpress. It allows you to do DB upgrades on deploy, instead of having to do
hacks like re-activating your plugin so it fires activation hooks again.

Also you don't have to worry about the PHP process timing out since it's
executed in the CLI context and not the browser.

## Usage

In your plugin, add a hook for the `schema_upgrade` action. It should behave
similar to https://codex.wordpress.org/Creating_Tables_with_Plugins. You should
write it in a way where it can repeatedly run without harm. For example, if one
of your plugins needs to create a new term:

````
add_action('schema_upgrade', function() {
  if(!term_exists('Category', 'segment')) {
    wp_insert_term('Category', 'segment');
  }
});
````

**Add the schema upgrade to your deploy process**

````
wp schema upgrade
````

When this runs, it will essentially call `do_action('schema_upgrade')`.

## License
Available under the MIT License.
