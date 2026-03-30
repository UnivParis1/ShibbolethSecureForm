LimeSurvey-ShibbolethAuth

LimeSurvey 2.05+ Secure form access by Shibboleth

LimeSurvey: http://www.limesurvey.org/en/

# Description

Le plugin permet une authentification Shibboleth au niveau du formulaire.

Il utilise la possibilité de Lazy Session de Shibboleth

# Configuration:

## Apache :

> [!IMPORTANT]
> Shibboleth doit être fonctionnel, l'application doit être déclaré auprès du fournisseur d'identité
> Le module apache *** mod_shib *** doit être installé et configuré sur le serveur

La directive suivante doit être renseignée sur le vhost site


```
<Location />
AuthType shibboleth
ShibRequestSetting requireSession false
ShibUseHeaders On
Require shibboleth
</Location>
```

1. ** Explication: **
- la directive _"requireSession false"_ permet d'avoir un accès potentiellement Shibbolétisé
- tout en ne bloquant pas l'accès aux formulaires qui ne font pas appel à l'authentification Shibboleth

## Interface limesurvey /admin

Ci dessous, la configuration frontend depuis l'interface admin de Limesurvey

### Configuration Globale :

_Dans configuration -> Paramètres -> Gestionnaire d'extensions -> ShibbolethSecureForm_

1. ** Label **
- _"URL that make the Shibboleth authentification and return the headers to the referrer"_:

2. ** ShibbolethSecureFormUrlAuth ** => doit être de type ``` https://HOST_LIMESURVEY/Shibboleth.sso/Login?target= ```
- Cette url sert de point d'entrée pour l'authentification.
le HOST doit être le même que Limesurvey.


### Configuration du formulaire :

Une fois le formulaire définit, dans les paramètres du formulaire :

1. ** Extensions simples ** -> ShibbolethSecureForm
- _"Secure the form by a Shibboleth Authentification"_ => ** booléen d'activation du plugin **

### Paramètres de l’extension ShibbolethSecureForm

Dans la configuration du formulaire, il y'a 2 paramètres à configurer :

- __Secure the form by a Shibboleth Authentification__ : utiliser ou non le plugin, si no/non, le plugin n'est pas pris en compte pour ce formulaire (mis par défaut)
- __Shibboleth Domain Authorized__ : obligatoire si le plugin est activé: le domaine unique sur lequel faire un filtre du nom de domaine. L'attribut Shib vérifié est: eppn
