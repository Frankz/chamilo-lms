<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
$cidReset = true;

require_once 'main/inc/global.inc.php';

api_block_anonymous_users();

$userId = api_get_user_id();
$userInfo = api_get_user_info();

$em = Database::getManager();

$form = new FormValidator('search', 'post', api_get_self());
$form->addHeader(get_lang('Diagnosis'));

/** @var ExtraFieldSavedSearch  $saved */
$search = [
    'user' => $userId
];

$items = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy($search);

$extraFieldSession = new ExtraField('session');
$extraFieldValueSession = new ExtraFieldValue('session');
//$extra = $extraField->addElements($form, '', [], true, true);

/*$extra = $extraField->addElements($form, '', [], true, true, array('heures-disponibilite-par-semaine'));
$elements = $form->getElements();
$variables = ['theme', 'domaine', 'competenceniveau', 'filiere'];
foreach ($elements as $element) {
    $element->setAttribute('extra_label_class', 'red_underline');
}

$htmlHeadXtra[] ='<script>
$(document).ready(function(){
	'.$extra['jquery_ready_content'].'
});
</script>';*/


$extraFieldValue = new ExtraFieldValue('user');
$wantStage = $extraFieldValue->get_values_by_handler_and_field_variable(api_get_user_id(), 'filiere_want_stage');
$hide = true;
if ($wantStage !== false) {
    $hide = $wantStage['value'] === 'yes';
}

$defaultValueStatus = 'extraFiliere.hide()';
if ($hide === false) {
    $defaultValueStatus = '';
}

$htmlHeadXtra[] ='<script>
$(document).ready(function() {

    var extraFiliere = $("input[name=\'extra_filiere[extra_filiere]\']").parent().parent().parent().parent();
    
    '.$defaultValueStatus.'
    
    $("input[name=\'extra_filiere_want_stage[extra_filiere_want_stage]\']").change(function() {
        if ($(this).val() == "no") {
            extraFiliere.show();
        } else {
            extraFiliere.hide();
        }
    });
    //

});
</script>';


$form->addButtonSave(get_lang('Save'), 'save');

$result = SessionManager::getGridColumns('simple');
$columns = $result['columns'];
$column_model = $result['column_model'];

$defaults = [];
$tagsData = [];
if (!empty($items)) {
    /** @var ExtraFieldSavedSearch $item */
    foreach ($items as $item) {
        $variable = 'extra_'.$item->getField()->getVariable();
        if ($item->getField()->getFieldType() == Extrafield::FIELD_TYPE_TAG) {
            $tagsData[$variable] = $item->getValue();
        }
        $defaults[$variable] = $item->getValue();
    }
}

$form->setDefaults($defaults);

//$view = $form->returnForm();
$filterToSend = '';

if ($form->validate()) {
    $params = $form->getSubmitValues();
    /** @var \Chamilo\UserBundle\Entity\User $user */
    $user = $em->getRepository('ChamiloUserBundle:User')->find($userId);

    if (isset($params['save'])) {
        // save


        MessageManager::send_message_simple(
            $userId,
            get_lang('DiagnosisFilledSubject'),
            get_lang('DiagnosisFilledDescription')
        );

        $drhList = UserManager::getDrhListFromUser($userId);
        if ($drhList) {
            foreach ($drhList as $drhId) {
                $subject = sprintf(get_lang('UserXHasFilledTheDiagnosis'), $userInfo['complete_name']);
                $content = sprintf(get_lang('UserXHasFilledTheDiagnosisDescription'), $userInfo['complete_name']);
                MessageManager::send_message_simple($drhId, $subject, $content);
            }
        }

        Display::addFlash(Display::return_message(get_lang('Saved')));
        header("Location: ".api_get_self());
        exit;
    } else {
        // Search
        $filters = [];
        // Parse params.
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
                continue;
            }
            if (!empty($value)) {
                $filters[$key] = $value;
            }
        }

        $filterToSend = [];
        if (!empty($filters)) {
            $filterToSend = ['groupOp' => 'AND'];
            if ($filters) {
                $count = 1;
                $countExtraField = 1;
                foreach ($result['column_model'] as $column) {
                    if ($count > 5) {
                        if (isset($filters[$column['name']])) {
                            $defaultValues['jqg'.$countExtraField] = $filters[$column['name']];
                            $filterToSend['rules'][] = ['field' => $column['name'], 'op' => 'cn', 'data' => $filters[$column['name']]];
                        }
                        $countExtraField++;
                    }
                    $count++;
                }
            }
        }
    }
}

$extraField = new ExtraField('user');

$userForm = new FormValidator('user_form', 'post', api_get_self());
$jqueryExtra = '';

/*$fieldsToShow = [
    'statusocial',
    'filiereprecision',
    'filiere',
    'heures-disponibilite-par-semaine',
    'datedebutstage',
    'datefinstage',
    'heures-disponibilite-par-semaine-stage',
    'poursuiteapprentissagestage',
    'objectif-apprentissage',
    'methode-de-travaille',
    'accompagnement'
];*/

$userForm->addHeader(get_lang('Filière'));
$fieldsToShow = [
    'statusocial',
    'filiere_user',
    'filiereprecision',
    'filiere_want_stage',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$fieldsToShow = [
    'filiere'
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);


$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHeader(get_lang('Disponibilité avant mon stage'));

$extra = $extraFieldSession->addElements(
    $userForm,
    '',
    [],
    true,
    true,
    array('access_start_date', 'access_end_date')
);
$jqueryExtra .= $extra['jquery_ready_content'];

$elements = $userForm->getElements();
$variables = ['access_start_date', 'access_end_date'];
foreach ($elements as $element) {
    $element->setAttribute('extra_label_class', 'red_underline');
}

$fieldsToShow = [
    'heures-disponibilite-par-semaine',
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHeader(get_lang('Disponibilité pendant mon stage'));

$fieldsToShow = [
    'datedebutstage',
    'datefinstage',
    'poursuiteapprentissagestage',
    'heures-disponibilite-par-semaine-stage'
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHeader(get_lang('Les thèmes qui m’intéressent / Mes objectifs d’apprentissage'));

$fieldsToShow = [
    'domaine',
    'theme'
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHeader(get_lang('Mon niveau de langue'));

$fieldsToShow = [
    'competenceniveau'
];

$extra = $extraFieldSession->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow,
    $defaults
);

$jqueryExtra .= $extra['jquery_ready_content'];

$userForm->addHeader(get_lang('Mes objectifs d’apprentissage'));

$fieldsToShow = [
    'objectif-apprentissage'
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

$userForm->addHeader(get_lang('Ma méthode de travail'));

$fieldsToShow = [
    'methode-de-travaille'
];

$extra = $extraField->addElements(
    $userForm,
    api_get_user_id(),
    [],
    true,
    true,
    $fieldsToShow,
    $fieldsToShow
);

/*echo '<pre>';
echo $jqueryExtra;
echo '</pre>';*/

$htmlHeadXtra[] ='<script>
$(document).ready(function(){
	'.$jqueryExtra.'
});
</script>';

$userForm->addButtonSave(get_lang('Save'));

$userForm->setDefaults($defaults);
$userFormToString = $userForm->returnForm();

if ($userForm->validate()) {
    // Saving to user profile
    $extraFieldValue = new ExtraFieldValue('user');
    $userData = $userForm->getSubmitValues();
    $extraFieldValue->saveFieldValues($userData);

    // Saving to extra_field_saved_search

    /** @var \Chamilo\UserBundle\Entity\User $user */
    $user = $em->getRepository('ChamiloUserBundle:User')->find($userId);

    $sessionFields = [
        'extra_access_start_date',
        'extra_access_end_date',
        'extra_filiere',
        'extra_domaine',
        'extra_temps-de-travail',
        'extra_competenceniveau',
        'extra_theme'
    ];

    foreach ($userData as $key => $value) {
        $found = strpos($key, '__persist__');

        if ($found === false) {
            continue;
        }

        $tempKey = str_replace('__persist__', '', $key);
        if (!isset($params[$tempKey])) {
            $params[$tempKey] = array();
        }
    }

    if (isset($userData['extra_filiere_want_stage']) &&
        isset($userData['extra_filiere_want_stage']['extra_filiere_want_stage'])
    ) {
        $wantStage = $userData['extra_filiere_want_stage']['extra_filiere_want_stage'];

        if ($wantStage === 'yes') {
            if (isset($userData['extra_filiere_user'])) {
                $userData['extra_filiere'] = [];
                $userData['extra_filiere']['extra_filiere'] = $userData['extra_filiere_user']['extra_filiere_user'];
            }
        }
    }

    // Parse params.
    foreach ($userData as $key => $value) {
        if (substr($key, 0, 6) != 'extra_' && substr($key, 0, 7) != '_extra_') {
            continue;
        }

        if (!in_array($key, $sessionFields)) {
            continue;
        }

        $field_variable = substr($key, 6);
        $extraFieldInfo = $extraFieldValueSession
            ->getExtraField()
            ->get_handler_field_info_by_field_variable($field_variable);

        if (!$extraFieldInfo) {
            continue;
        }

        $extraFieldObj = $em->getRepository('ChamiloCoreBundle:ExtraField')->find($extraFieldInfo['id']);

        $search = [
            'field' => $extraFieldObj,
            'user' => $user
        ];

        /** @var ExtraFieldSavedSearch  $saved */
        $saved = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findOneBy($search);

        if ($saved) {
            $saved
                ->setField($extraFieldObj)
                ->setUser($user)
                ->setValue($value)
            ;
            $em->merge($saved);

        } else {
            $saved = new ExtraFieldSavedSearch();
            $saved
                ->setField($extraFieldObj)
                ->setUser($user)
                ->setValue($value)
            ;
            $em->persist($saved);
        }
        $em->flush();
    }
    Display::addFlash(Display::return_message(get_lang('Saved')));
    header('Location:'.api_get_self());
    exit;
}

$tpl = new Template(get_lang('Diagnosis'));
$tpl->assign('form', $userFormToString);
$content = $tpl->fetch('default/user_portal/search_extra_field.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
