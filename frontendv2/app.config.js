module.exports = {
  expo: {
    name: "GoGoBudgeto",
    slug: "gogobudgeto",
    version: "1.0.0",
    orientation: "portrait",
    icon: "./assets/icon.png",
    userInterfaceStyle: "light",
    splash: {
      image: "./assets/splash.png",
      resizeMode: "contain",
      backgroundColor: "#ffffff"
    },
    assetBundlePatterns: ["**/*"],
    ios: {
      supportsTablet: true,
      bundleIdentifier: "com.gogobudgeto.app"
    },
    android: {
      adaptiveIcon: {
        foregroundImage: "./assets/adaptive-icon.png",
        backgroundColor: "#ffffff"
      },
      package: "com.gogobudgeto.app"
    },
    web: {
      favicon: "./assets/favicon.png",
    },
    scheme: "com.gogobudgeto.app", // Using reverse domain notation scheme
    extra: {
      apiUrl: "http://127.0.0.1:8000/api"
    },
    plugins: [
      "expo-secure-store"
    ]
  }
};