<?php

/**
 * shibbolethSecureForm Plugin for LimeSurvey
 *
 * @author Etienne Bohm <Etienne.Bohm@univ-paris1.fr>
 * @copyright 2016 Etienne Bohm <http://univ-paris1.fr>
 * @license AGPL v3
 * @version 0.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */
class ShibbolethSecureForm extends \LimeSurvey\PluginManager\PluginBase {

    protected $storage = 'DbStorage';
    static protected $description = 'Shibboleth Secure Form';
    static protected $name = 'ShibbolethSecureForm';
    protected $settings = array(
        'ShibbolethSecureFormUrlAuth' => array(
            'type' => 'string',
            'label' => 'URL that make the Shibboleth authentification and return the headers to the referrer',
        ),
        'ShibbolethDefaultDomain' => array(
            'type' => 'string',
            'label' => 'Default Shibboleth Domain Authorized',
        )
    );

    public function init() {
        $this->subscribe('newSurveySettings');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('beforeSurveyPage');
    }

    public function beforeSurveySettings() {
        $ShibbolethSecureFormUrlAuth = $this->get('ShibbolethSecureFormUrlAuth', null, null, null);
        $ShibbolethDefaultDomain = $this->get('ShibbolethDefaultDomain', null, null, null);

        if (!($ShibbolethSecureFormUrlAuth && $ShibbolethDefaultDomain)) {
            $event = $this->getEvent();
            $event->set("surveysettings.{$this->id}", array(
                'name' => get_class($this),
                'settings' => array(
                    'title' => array(
                        'type' => 'info',
                        'content' => '<legend><small>Shibboleth Secure Form</small></legend>'
                    ),
                    'info' => array(
                        'type' => 'info',
                        'content' => '<span color="red">Shibboleth Secure Form Global var are not setted</span>'
                    ),
                )
            ));

            return;
        }

        $event = $this->getEvent();
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'title' => array(
                    'type' => 'info',
                    'content' => '<legend><small>Shibboleth Secure Form</small></legend>'
                ),
                'ShibbolethSurvey' => array(
                    'type' => 'select',
                    'options' => array(
                        0 => 'No',
                        1 => 'Yes'
                    ),
                    'label' => 'Secure the form by a Shibboleth Authentification',
                    'current' => $this->get(
                            'ShibbolethSurvey', 'Survey', $event->get('survey')
                    ),
                ),
                'ShibbolethDomain' => array(
                    'type' => 'string',
                    'label' => 'Shibboleth Domain Authorized',
                    'current' => $this->get('ShibbolethDomain', 'Survey', $event->get('survey'), $ShibbolethDefaultDomain)
                ),
                'ShibbolethFilterAttribute' => array(
                    'type' => 'select',
                    'label' => "Attribut shibboleth utilisé pour filtrer l'accès",
                            'options' => array(
                            'null' => 'Aucun',
                            'unscoped-affiliation' => 'unscoped-affiliation',
                        ),
                    'current' => $this->get('ShibbolethFilterAttribute', 'Survey', $event->get('survey'), 'null')
                    ),
                'ShibbolethFilterText' => array(
                    'type' => 'text',
                    'label' => 'Filtrage sur ces valeurs',
                    'help' => 'Si plusieurs valeurs, les mettres sur plusieurs lignes',
                    'current' => $this->get('ShibbolethFilterText', 'Survey', $event->get('survey'), 'null')
                ),
            )
        ));
    }

    //28/Jan/2015: To be honest,  I have NO idea what this function does. It's not documented anywhere, but after a lot of trial and error I discovered it _IS NECESSARY_ for any per-survey settings to actually hold. Todo: Perhaps this should be integrated into the core?    
    public function newSurveySettings() {
        $event = $this->getEvent();
        foreach ($event->get('settings') as $name => $value) {
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }

    public function beforeSurveyPage() {
        $event = $this->getEvent();

        $isSurveyShib = $this->get('ShibbolethSurvey', 'Survey', $event->get('surveyId'));

        if (!$isSurveyShib) {
            return;
        }

        $urlAuthShib = $this->get('ShibbolethSecureFormUrlAuth', null, null);

        if (!(isset($_SERVER["HTTP_SHIB_IDENTITY_PROVIDER"]) == true && strlen($_SERVER["HTTP_SHIB_IDENTITY_PROVIDER"]) > 0)) {

            if ($_GET['pluginshibblimesurveyredirect'] == true) {
                throw new CHttpException(500, 'Infinite loop credential shibboleth');
            }

            $urlEncoded = urlencode(Yii::app()->request->hostInfo . Yii::app()->request->url . (strstr(Yii::app()->request->url, '?') ? '&' : '?') . 'pluginshibblimesurveyredirect=true');

            $redirectUrl = $urlAuthShib . $urlEncoded;

            Yii::app()->request->redirect($redirectUrl);
        }

        $domainsShib = explode(';', $this->get('ShibbolethDomain', 'Survey', $event->get('surveyId')));

        $eppns = explode('@', $_SERVER['eppn']);

        $domain = $eppns[1];

        if (array_search($domain, $domainsShib) === false) {
            throw new CHttpException(401, 'Wrong credentials for this survey.');
        }

        $ShibbolethFilterAttribute = $this->get('ShibbolethFilterAttribute', 'Survey', $event->get('surveyId'));

        if ($ShibbolethFilterAttribute == 'null') {
            return;
        }

        if ( ! isset($_SERVER[$ShibbolethFilterAttribute]) )
            throw new CHttpException(500, "Erreur configuration plugin ShibbolethSecureForm, l'attribut $ShibbolethFilterAttribute n'existe pas dans les variables serveur, veuillez contacter la DSIUN");

        $attribute = $_SERVER["unscoped-affiliation"];
        $ShibbolethFilterText = $this->get('ShibbolethFilterText', 'Survey', $event->get('surveyId'));

        if (strlen($ShibbolethFilterText) == 0)
            return;

        $filterValues = preg_split('/\r\n|\r|\n/', $ShibbolethFilterText);

        $test = false;
        foreach ($filterValues as $value) {
            if (str_contains($attribute, $value)) {
                $test = true;
            }
        }

        if (!$test)
            throw new CHttpException(401, 'Wrong credentials for this survey.');

    }

}
