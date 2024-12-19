<?php

App::uses('CoPerson', 'Model');

class ItrsseppnAssigner extends AppModel {
  // Required by COmanage Plugins
  public $cmPluginType = "identifierassigner";

  // Mapping of primary campus to ePPN scope
  protected $primaryCampusToScopeMap = array(
    'C' => 'missouri.edu',
    'H' => 'umh.edu',
    'K' => 'umkc.edu',
    'R' => 'mst.edu',
    'S' => 'umsl.edu',
    'U' => 'umsystem.edu',
    // N is a current bug in upstream SOR and will be updated.
    'N' => 'missouri.edu'
  );

  /**
   * Assign a new Identifier.
   *
   * @since  COmanage Registry v4.4.0
   * @param  int                              $coId           CO ID for Identifier Assignment
   * @param  IdentifierAssignmentContextEnum  $context        Context in which to assign Identifier
   * @param  int                              $recordId       Record ID of type $context
   * @param  string                           $identifierType Type of identifier to assign
   * @param  string                           $emailType      Type of email address to assign
   * @return string
   * @throws InvalidArgumentException
   */
  
  public function assign($coId, $context, $recordId, $identifierType, $emailType=null) {
    if($context != IdentifierAssignmentContextEnum::CoPerson) {
      throw new InvalidArgumentException('NOT IMPLEMENTED');
    }

    // Pull the CO Person and associated Identifiers.
    $CoPerson = new CoPerson();

    $args = array();
    $args['conditions']['CoPerson.id'] = $recordId;
    $args['contain'] = array('Identifier');

    $coPerson = $CoPerson->find('first', $args);

    if(empty($coPerson)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.co_people.1'), $recordId)));
    }

    // Find the primary campus.
    $i = Hash::extract($coPerson['Identifier'], '{n}[type=primarycampus]');
    $pc = $i[0]['identifier'];

    if(empty($pc)) {
      throw new InvalidArgumentException(_txt('er.itrsseppnassigner.primarycampus'));
    }

    // Find the userPrincipalName.
    $i = Hash::extract($coPerson['Identifier'], '{n}[type=upn]');
    $upn = $i[0]['identifier'];

    if(empty($upn)) {
      throw new InvalidArgumentException(_txt('er.itrsseppnassigner.upn'));
    }

    // Compute the ePPN scope based on primary campus.
    $scope = ($this->primaryCampusToScopeMap)[$pc];

    $eppn = explode('@', $upn)[0] . '@'. $scope;

    return $eppn;
  }
}
