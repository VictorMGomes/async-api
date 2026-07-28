# Upgrading

## Upgrading To 2.0.0 From 1.x

Version 2.0.0 introduces automated static analysis and brings some breaking changes to the configuration and routes.

### 1. Routes Renamed

The documentation viewer routes have been renamed to better reflect Laravel's broadcasting terminology:

- `/docs/ws` is now `/docs/broadcast`
- `/docs/ws/json` is now `/docs/broadcast/json`

If you have any custom links or bookmarks to the documentation, make sure to update them.

### 2. Configuration Structure Flattened

The configuration file `config/async-api.php` has been simplified and flattened.

If you previously published the configuration file, you must publish the new version and migrate your settings:

```bash
php artisan vendor:publish --tag="async-api-config" --force
```

Key changes in the config structure:

- The `server` array has been flattened into `server_url`, `server_description`, and `server_protocol`.
- The `info` array has been flattened into `info_title`, `info_version`, and `info_description`.
- Added a `debug` option.

### 3. Automated Schema Discovery (Plug-and-Play)

The `#[AsyncApi]` attribute is now completely optional!

You no longer need to add `#[AsyncApi]` to your events. The package will automatically scan any class implementing `ShouldBroadcast` and infer its schema via static code analysis.

You can safely remove the `#[AsyncApi]` attribute from your events unless you need to override specific information (like a custom channel name or description).
