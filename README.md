LimeSurvey-ShibbolethAuth

LimeSurvey 2.05+ Secure form access by Shibboleth

LimeSurvey: http://www.limesurvey.org/en/

Le plugin permet une authentification Shibboleth au niveau du formulaire.

Il utilise la possibilité de Lazy Session de Shibboleth



Configuration:

Apache :

Le module apache mod_shib doit être installé et configuré sur le serveur

La directive suivante doit être renseignée sur le vhost site

<Location />
AuthType shibboleth
ShibRequestSetting requireSession false
ShibUseHeaders On
Require shibboleth
</Location>

Explication: la directive "requireSession false" permet d'avoir un accès potentiellement Shibbolétisé tout en ne bloquant pas l'accès aux formulaires qui ne font pas appel à l'authentification Shibboleth

Limesurvey filesystem :

Copier le fichier ShibbolethSecureForm dans le répertoire plugins de Limesurvey :

www/plugins/ShibbolethSecureForm.php

Interface limesurvey admin :

Configuration Globale :

Dans configuration -> Paramètres -> Gestionnaire d'extensions -> ShibbolethSecureForm

Label : "URL that make the Shibboleth authentification and return the headers to the referrer":

ShibbolethSecureFormUrlAuth => doit être de type https://HOST_LIMESURVEY/Shibboleth.sso/Login?target=

Cette url sert de point d'entrée pour l'authentification.
le HOST doit être le même que Limesurvey.


Configuration du formulaire :

Une fois le formulaire définit, dans les paramètres du formulaire :

Extensions simples -> ShibbolethSecureForm
"Secure the form by a Shibboleth Authentification" : booléen d'activation du plugin
