# flutter_local_notifications schedules notifications via Gson reflection,
# which R8 can't see through — without these keep rules, minified release
# builds can fail to restore scheduled notifications after a device reboot.
-keep class com.dexterous.** { *; }
-keep class * extends com.google.gson.TypeAdapter { *; }
-keep class * implements com.google.gson.TypeAdapterFactory { *; }
-keep class * implements com.google.gson.JsonSerializer { *; }
-keep class * implements com.google.gson.JsonDeserializer { *; }

-dontwarn org.conscrypt.**
-dontwarn com.google.android.play.core.**
