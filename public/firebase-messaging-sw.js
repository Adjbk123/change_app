importScripts('https://www.gstatic.com/firebasejs/11.10.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/11.10.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyDbypnETo3JdYQXsQSuL3VVLMNI-FA_Epk",
    authDomain: "myswap-466213.firebaseapp.com",
    projectId: "myswap-466213",
    storageBucket: "myswap-466213.firebasestorage.app",
    messagingSenderId: "357745833295",
    appId: "1:357745833295:web:fb2a44f72dd903a643813a",
    measurementId: "G-NK6RJMXHES"
});

const messaging = firebase.messaging();

// Affiche la notification quand un message push est reçu en arrière-plan
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw.js] Message reçu en arrière-plan : ', payload);
    const notificationTitle = payload.notification.title || 'Notification';
    const notificationOptions = {
        body: payload.notification.body || '',
        icon: '/favicon.ico', // Change l'icône si besoin
        data: payload.data || {}
    };
    self.registration.showNotification(notificationTitle, notificationOptions);
});
