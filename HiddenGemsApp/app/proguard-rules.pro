# Retrofit and OkHttp include their R8 rules. Keep API data models for reflective Gson parsing.
-keep class za.co.hiddengems.app.data.** { *; }
-keepattributes Signature
