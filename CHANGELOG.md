# Changelog

## 0.4.0 (unreleased)

- Renamed the package to `yii2-ext/yii2-daemon`.
- Require PHP 8.1 and Yii2 `^2.0.45`.
- Replaced the `react/react` 0.4 metapackage with `react/event-loop` ^1.6.
- Use `Loop::get()` instead of the deprecated `Factory::create()`.
- Create the loop lazily; handle `SIGINT`/`SIGTERM` when `ext-pcntl` is available.
- Added `beforeStop()` and `afterDaemon()` hooks, PHPUnit tests, PHPStan and CI.

The signatures of `init()`, `actionDaemon()`, `prepare()` and the visibility of
`$loop` are kept for backwards compatibility.
