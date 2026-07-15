Pour avoir un vrai .apk installable + offline + synchro, voici les 3 options réalistes. Ton front actuel (HTML/CSS/JS) est réutilisable dans 2 des 3 :

Option	APK réel	Réécriture	Offline	Synchro	Effort
Capacitor (encapsule ton site)	✅	Aucune (ton code tel quel)	✅ (SW + cache)	✅ (ton API REST)	Faible
PWA	❌ (raccourci accueil)	Aucune	✅	✅	Très faible
Flutter	✅	Totale (Dart)	✅	✅	Élevé
Ma recommandation : Capacitor.

Il prend ton index.html + app.js + css et génère un vrai APK Android (et iOS ensuite).
Offline : un service worker met en cache l'app + les données (IndexedDB).
Synchro des connectés : ton API REST existe déjà. On ajoute un cache local (ex. localStorage/IndexedDB) qui stocke les ventes/dépenses en local et pousse vers l'API dès qu'il y a du réseau, et récupère le dashboard au retour online.
Tu gardes l'auth par téléphone et la structure DB telle quelle.
Le point à valider avant de commencer (car ça dépasse le "MVP test d'idée" qu'on avait dit) :

1 sur 2 questions



Quelle approche veux-tu pour l'APK + offline + sync ?
Sélectionnez une réponse

Capacitor (recommandé)
Vrai APK Android à partir de ton code actuel. Offline + sync via ton API. Réécriture nulle.

PWA simple
Pas de vrai APK, mais installable sur l'accueil + offline + sync. Le plus rapide, pas de build natif.

Flutter complet
Vraie app native, mais réécriture totale du front en Dart. Lourd pour un MVP.

Tapez votre propre réponse
Tapez votre réponse...