// Top-level build file — applies plugins via the version catalog
// declared in gradle/libs.versions.toml. Sub-modules declare which
// plugins they actually use.

plugins {
    alias(libs.plugins.android.application) apply false
    alias(libs.plugins.kotlin.android) apply false
    alias(libs.plugins.kotlin.compose) apply false
    alias(libs.plugins.kotlin.serialization) apply false
}
