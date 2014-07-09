<?php

class Person {

  public static $_gedcom;
  public static $_people;
  public static $generationsToName = 10;
  var $_data;
  var $_father;
  var $_mother;
  var $_siblings;
  var $_children;
  var $_spouses;
  var $_parents = array( );
  var $_ancestorIDs = null;

  function __construct ( $id = null, $gedcom = null ) {
    if ( $gedcom === null ) $gedcom = self::$_gedcom;
    if ( ! $id ) {
      $this->_data = array( );
      return;
    }
    if ( is_array( $id )) $id = self::_id( $id );
    if ( isset( $gedcom['INDI'][ $id ] )) {
      $this->_data = $gedcom['INDI'][ $id ];
      return;
    }
  }

  public static function singleton ( $data = null ) {
    if ( ! isset( self::$_people )) {
      self::$_people = array( );
    }
    $id = self::_id( $data );
    if ( ! $id ) $id = -1;
    if ( ! isset( self::$_people[ $id ] )) {
      $class = __CLASS__;
      self::$_people[ $id ] = new $class( $id );
    }
    return self::$_people[ $id ];
  }

  static function parse ( $file ) {
    $gedcom = array( );
    $file = fopen( $file, 'r' );
    $id = null;
    while ( ! feof( $file )) {
      $line = trim( fgets( $file ));
      if ( preg_match( '/^0 @([A-Z0-9]+)@ (\w*)/', $line, $match )) {
        $id = $match[1];
        $type = $match[2];
        if ( ! isset( $gedcom[ $type ] )) $gedcom[ $type ] = array( );
        if ( ! isset( $gedcom[ $type ][ $id ] )) $gedcom[ $type ][ $id ] = array( '_ID' => $id );
      }
      elseif ( $id && preg_match( '/^(\d+)\s+(\w+)\s*(.*)/', $line, $match )) {
        $num = $match[1] / 1;
        $tag = $match[2];
        $data = $match[3];
        if ( $num == 1 ) {
          $masterTag = $tag;
          if ( ! isset( $gedcom[ $type ][ $id ][ $masterTag ] )) $gedcom[ $type ][ $id ][ $masterTag ] = array( );
          if ( ! isset( $gedcom[ $type ][ $id ][ $masterTag ][ $tag ] )) $gedcom[ $type ][ $id ][ $masterTag ][ $tag ] = array( );
          array_push( $gedcom[ $type ][ $id ][ $masterTag ][ $tag ], $data );
        }
        elseif ( $num == 2 ) {
          if ( ! isset( $gedcom[ $type ][ $id ][ $masterTag ][ $tag ] )) $gedcom[ $type ][ $id ][ $masterTag ][ $tag ] = array( );
          array_push( $gedcom[ $type ][ $id ][ $masterTag ][ $tag ], $data );
        }
        // if ( ! isset( $gedcom[ $type ][ $id ]['GEDCOM'] )) $gedcom[ $type ][ $id ]['GEDCOM'] = '';
        // $gedcom[ $type ][ $id ]['GEDCOM'] .= $line . "\n";
      }
    }
    fclose( $file );
    self::$_gedcom = $gedcom;
    return $gedcom;
  }

  static function _id ( $data ) {
    if ( ! $data ) return null;
    if ( is_array( $data )) {
      if ( isset( $data['_ID'] )) return self::_id( $data['_ID'] );
      if ( isset( $data[0] )) return self::_id( $data[0] );
    }
    return @preg_replace( '/@/', '', $data );
  }

  function data ( $tag ) {
    if ( ! isset( $this->_data[ $tag ] )) return null;
    return $this->_data[ $tag ];
  }

  function id ( ) {
    return substr( $this->_data['_ID'], 1 );
  }

  function gender ( ) {
    if ( ! isset( $this->_data['SEX'] )) return null;
    return $this->_data['SEX']['SEX'][0];
  }

  function childType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'son' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'daughter' );
    return self::i18n( 'child' );
  }

  function grandChildType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'grandson' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'granddaughter' );
    return self::i18n( 'grandchild' );
  }

  function siblingType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'brother' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'sister' );
    return self::i18n( 'sibling' );
  }

  function parentSiblingType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'uncle' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'aunt' );
  }

  function siblingChildType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'nephew' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'niece' );
  }

  function parentType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'father' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'mother' );
    return self::i18n( 'parent' );
  }

  function grandParentType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'grandfather' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'grandmother' );
    return self::i18n( 'grandparent' );
  }

  function greatGrandParentType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'great-grandfather' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'great-grandmother' );
    return self::i18n( 'great-grandparent' );
  }

  function greatGrandChildType ( ) {
    if ( $this->gender( ) == 'M' ) return self::i18n( 'great-grandson' );
    if ( $this->gender( ) == 'F' ) return self::i18n( 'great-granddaughter' );
    return self::i18n( 'great-grandchild' );
  }

  function _urlise ( $name ) {
    $find = array( '/\s+/', '/[^\w\'+()-]/' );
    $replace = array( '+', '' );
    return strtolower( preg_replace( $find, $replace, trim( $name )));
  }

  function _partOfName ( $part = 'NAME', $link = false, $years = false ) {
    if ( ! isset( $this->_data['NAME'][ $part ] )) return 'unknown';
    $name = trim( preg_replace( '/\//', '', $this->_data['NAME'][ $part ][0] ));
    if ( $years ) $name .= ' ' . $this->years( );
    if ( ! $link ) return $name;
    return '[[' . $this->link( ) . ' ' . ' ' . $name . ']]';
  }

  /**
   * need to override this function when you initialise
   * for example:
   *   $person->link = function ( ) { return 'http://foo.com/tree/' + $this->id( ); };
   *   $person->link = function ( ) { return 'http://foo.com/search?name=' + $this->_urlise( $this->surname( )); };
   * etc
   * I'll come back to this...
   */
  function link ( ) {
    return 'http://www.clarkeology.com/names/' . $this->_urlise( $this->surname( )) . '/' . $this->id( ) . '/' . $this->_urlise( $this->forename( ));
    // return 'http://www.clarkeology.com/wiki/' . $this->_urlise( $this->name( ));
  }

  function name ( $link = false, $years = false ) {
    return $this->_partOfName( 'NAME', $link, $years );
  }

  function forename ( ) {
    return substr( $this->name( ), 0, stripos( $this->name( ), $this->surname( )) - 1 );
  }

  function surname ( $link = false ) {
    return $this->_partOfName( 'SURN', $link );
  }

  function occupation ( ) {
    if ( ! isset( $this->_data['OCCU'] )) return null;
    return $this->tagToLabel( 'OCCU' ) . ' ' . implode( $this->_data['OCCU']['OCCU'] );
  }

  function source ( $ids = array( )) {
    $sources = array( );
    foreach ( $ids as $id ) {
      $id = $this->_id( $id );
      foreach ( array( '_TYPE', 'TEXT' ) as $tag ) {
        if ( isset( self::$_gedcom['SOUR'][$id][$tag] )) {
          foreach ( self::$_gedcom['SOUR'][$id][$tag][$tag] as $source ) {
            array_push( $sources, $source );
          }
        }
      }
    }
    return implode( ', ', $sources );
  }

  function notes ( ) {
    if ( ! isset( $this->_data['NOTE'] )) return null;
    $notes = array( );
    foreach ( $this->_data['NOTE']['NOTE'] as $id ) {
      $id = $this->_id( $id );
      foreach ( self::$_gedcom['NOTE'][$id]['CONC']['CONC'] as $note ) {
        array_push( $notes, trim( $note ));
      }
    }
    return implode( '', $notes );
  }

  function familiesWithParents ( ) {
    if ( ! isset( $this->_data['FAMC'] )) return null;
    $familyID = $this->_id( $this->_data['FAMC']['FAMC'][0] );
    return self::$_gedcom['FAM'][$familyID];
  }

  function familiesWithSpouse ( ) {
    if ( ! isset( $this->_data['FAMS'] )) return array( );
    $families = array( );
    foreach ( $this->_data['FAMS']['FAMS'] as $family ) {
      $familyID = $this->_id( $family );
      array_push( $families, self::$_gedcom['FAM'][$familyID] );
    }
    return $families;
  }

  function mother ( ) {
    if ( ! $this->_mother ) {
      $family = $this->familiesWithParents( );
      $mother = isset( $family['WIFE'] ) ? $family['WIFE']['WIFE'][0] : null;
      $this->_mother = self::singleton( $mother );
    }
    return $this->_mother;
  }

  function father ( ) {
    if ( ! $this->_father ) {
      $family = $this->familiesWithParents( );
      $father = isset( $family['HUSB'] ) ? $family['HUSB']['HUSB'][0] : null;
      $this->_father = self::singleton( $father );
    }
    return $this->_father;
  }

  function spouses ( ) {
    if ( ! $this->_spouses ) {
      $this->_spouses = array( );
      foreach ( $this->familiesWithSpouse( ) as $family ) {
        foreach ( array( 'WIFE', 'HUSB' ) as $tag ) {
          if ( isset( $family[ $tag ] )) {
            foreach ( $family[ $tag ][ $tag ] as $spouse ) {
              $spouse = self::singleton( $spouse );
              if ( $spouse->id( ) != $this->id( )) array_push( $this->_spouses, $spouse );
            }
          }
        }
      }
    }
    return $this->_spouses;
  }

  function siblings ( ) {
    if ( ! $this->_siblings ) {
      $this->_siblings = array( );
      $family = $this->familiesWithParents( );
      if ( isset( $family['CHIL'] )) {
        foreach ( $family['CHIL']['CHIL'] as $child ) {
          $sibling = self::singleton( $child );
          if ( $sibling->id( ) != $this->id( )) array_push( $this->_siblings, $sibling );
        }
      }
    }
    return $this->_siblings;
  }

  function children ( ) {
    if ( ! $this->_children ) {
      $this->_children = array( );
      foreach ( $this->familiesWithSpouse( ) as $family ) {
        if ( isset( $family['CHIL'] )) {
          foreach ( $family['CHIL']['CHIL'] as $child ) {
            array_push( $this->_children, self::singleton( $child ));
          }
        }
      }
    }
    return $this->_children;
  }

  function _year ( $type = 'BIRT', $schema = false ) {
    if ( ! isset( $this->_data[ $type ] )) return '';
    if ( ! isset( $this->_data[ $type ]['DATE'] )) return '';
    $time = strtotime( $this->_data[ $type ]['DATE'][0] );
    $date = $time ? date( 'Y-m-d', $time ) : $this->_data[ $type ]['DATE'][0];
    if ( $date == date( 'Y-m-d' )) $date = $this->_data[ $type ]['DATE'][0];
    // error_log( __METHOD__ . ' ' . $this->_data[ $type ]['DATE'][0] . ' ' . $time . ' ' . $date );
    if ( preg_match( '/(\d{4})(-(\d\d)-(\d\d))?/', $date, $match )) {
      // error_log( __METHOD__ . ' ' . json_encode( $match ));
      if ( $schema && ( $type == 'BIRT' || $type == 'DEAT' )) {
        $itemprop = $type == 'BIRT' ? 'birthDate' : 'deathDate';
        $html = '<span itemprop="' . $itemprop . '" content="' . $date . '">' . $match[1] . '</span>';
        // error_log( $html );
        return $html;
      }
      return $match[1];
    }
    return '';
  }

  function _date ( $type = 'BIRT' ) {
    if ( ! isset( $this->_data[ $type ] )) return 'unknown';
    if ( isset( $this->_data[ $type ]['DATE'] )) return $this->_data[ $type ]['DATE'][0];
    if ( isset( $this->_data[ $type ]['PLAC'] )) return $this->_data[ $type ]['PLAC'][0];
    return 'unknown';
  }

  function dates ( ) {
    if ( ! isset( $this->_data['DEAT'] )) return '';
    return '(' . $this->_date( 'BIRT' ) . ' - ' . $this->_date( 'DEAT' ) . ')';
  }

  function years ( $schema = false ) {
    $birth = $this->_year( 'BIRT' );
    $death = $this->_year( 'DEAT' );
    if ( ! ( $birth || $death )) return '';
    return $this->_year( 'BIRT', $schema ) . ' - ' . $this->_year( 'DEAT', $schema );
  }

  function tagToLabel ( $tag ) {
    if ( $tag == 'BIRT' ) return ucfirst( self::i18n( 'born' ));
    if ( $tag == 'BAPM' ) return ucfirst( self::i18n( 'baptised' ));
    if ( $tag == 'DEAT' ) return ucfirst( self::i18n( 'died' ));
    if ( $tag == 'BURI' ) return ucfirst( self::i18n( 'buried' ));
    if ( $tag == 'OCCU' ) return ucfirst( self::i18n( 'occupation' ));
    return $tag;
  }

  function timeAndPlace ( $tag ) {
    $label = $this->tagToLabel( $tag );
    if ( ! isset( $this->_data[ $tag ] )) return null;
    $return = array( ucfirst( $label ));
    if ( isset( $this->_data[ $tag ]['DATE'] )) array_push( $return, $this->_data[ $tag ]['DATE'][0] );
    if ( isset( $this->_data[ $tag ]['PLAC'] )) array_push( $return, $this->_data[ $tag ]['PLAC'][0] );
    if ( isset( $this->_data[ $tag ]['NOTE'] )) array_push( $return, '(' . $this->_data[ $tag ]['NOTE'][0] . ')' );
    if ( isset( $this->_data[ $tag ]['SOUR'] )) array_push( $return, '(source: ' . $this->source( $this->_data[ $tag ]['SOUR'] ) . ')' );
    return implode( ' ', $return );
  }

  function tableTree ( $schema = false ) {
    $html = '<table id="family" summary="' . $this->name( ) . ' family tree"';
    if ( $schema ) $html .= ' itemscope itemtype="http://schema.org/Person"';
    $html .= '>';
    $children = array_map( function( $child ) { return $child->name( true ); }, $this->children( ));
    $c = count( $children );
    if ( ! $c ) $c = 1;
    $html .= '<tr>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->father( )->father( )->father( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->father( )->father( )->mother( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->father( )->mother( )->father( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->father( )->mother( )->mother( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->mother( )->father( )->father( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->mother( )->father( )->mother( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->mother( )->mother( )->father( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="ggparent" colspan="' . $c . '">';
    $html .= $this->mother( )->mother( )->mother( )->name( true, true );
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="gparent" colspan="' . $c * 2 . '">';
    $html .= $this->father( )->father( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="gparent" colspan="' . $c * 2 . '">';
    $html .= $this->father( )->mother( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="gparent" colspan="' . $c * 2 . '">';
    $html .= $this->mother( )->father( )->name( true, true );
    $html .= '</td>';
    $html .= '<td class="gparent" colspan="' . $c * 2 . '">';
    $html .= $this->mother( )->mother( )->name( true, true );
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td colspan="' . $c * 4 . '">';
    $html .= $this->father( )->name( true );
    $html .= '</td>';
    $html .= '<td colspan="' . $c * 4 . '">';
    $html .= $this->mother( )->name( true );
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="self" colspan="' . $c * 8 . '"';
    if ( $schema ) $html .= ' itemprop="name"';
    $html .= '>';
    $html .= $this->name( );
    if ( $schema ) {
      $html .= ' ' . $this->years( true );
    }
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= implode( array_map( function( $child ) { return '<td colspan="8">' . $child->name( true ) . '</td>'; }, $this->children( )));
    $html .= '</tr>';
    $html .= '</table>';
    return $html;
  }

  /**
   * A lot of wild guessing here if we have no death record
   */
  function isAlive ( ) {
    if ( $this->data( 'DEAT' )) return false;
    if ( $this->_year( 'BIRT' ) && ( $this->_year( 'BIRT' ) < 1900 )) return false;
    foreach ( $this->children( ) as $child ) {
      if ( $child->_year( 'BIRT' ) && ( $child->_year( 'BIRT' ) < 1930 )) return false;
      foreach ( $child->children( ) as $grandchild ) {
        if ( $grandchild->_year( 'BIRT' ) && ( $grandchild->_year( 'BIRT' ) < 1960 )) return false;
        if ( $grandchild->children( )) return false;
      }
    }
    return true;
  }

  function isPrivate ( ) {
    return $this->isAlive( );
  }

  /**
   * Get one "level" of parents, grandparent etc
   * $this->parentIDs( ); // parents
   * $this->parentIDs( 2 ); // grandparents
   * $this->parentIDs( 3 ); // great-grandparents
   */
  function parentIDs ( $levelRequired = 1, $thisLevel = 1 ) {
    $ancestors = array( );
    foreach ( array( 'father', 'mother' ) as $parent ) {
      if ( $this->$parent( )->id( )) {
        if ( $levelRequired == $thisLevel ) {
          array_push( $ancestors, $this->$parent( )->id( ));
        }
        else {
          $ancestors = array_merge( $ancestors, $this->$parent( )->parentIDs( $levelRequired, $thisLevel + 1 ));
        }
      }
    }
    return $ancestors;
  }

  function ancestorIDs ( ) {
    $ancestors = array( );
    if ( $this->father( )->id( )) {
      array_push( $ancestors, $this->father( )->id( ));
      $ancestors = array_merge( $ancestors, $this->father( )->ancestorIDs( ));
    }
    if ( $this->mother( )->id( )) {
      array_push( $ancestors, $this->mother( )->id( ));
      $ancestors = array_merge( $ancestors, $this->mother( )->ancestorIDs( ));
    }
    return $ancestors;
  }
  
  function hasAncestor ( $person, $level = 1, $debug = false ) {
    // if ( $debug ) error_log( __METHOD__ . ' ' . $person->name( ) . ' / ' . $this->name( ));
    foreach ( array( 'father', 'mother' ) as $parent ) {
      // if ( $debug ) error_log( __METHOD__ . ' ' . $this->name( ) . '\'s ' . $parent . ' is / ' . $this->$parent( )->name( ));
      if ( $this->$parent( )->id( )) {
        if ( $debug ) error_log( __METHOD__ . ' ' . $person->id( ) . ' == ' . $this->$parent( )->id( ) . '?' );
        if ( $person->id( ) == $this->$parent( )->id( )) {
          return $level;
        }
        if ( $debug ) error_log( __METHOD__ . ', no so test ancestors of ' . $parent );
        if ( $level = $this->$parent( )->hasAncestor( $person, $level + 1, $debug )) {
          return $level;
        }
      }
    }
    return false;
  }

  /**
   * Return the relationship between this person and a list of potential others, as a sentence
   */
  function relationship ( $people = array( )) {
    foreach ( $people as $person ) {
      if ( $relationship = $this->_relationship( $person )) {
        return $this->name( ) . ' is ' . $relationship . ' to ' . $person->name( true );
      }
    }
  }

  /**
   * @todo
   */
  static function i18n ( $string ) {
    return $string;
  }

  static function commodore ( $number ) {
    if ( $number == 1 ) return self::i18n( 'once' );
    if ( $number == 2 ) return self::i18n( 'twice' );
    return $number . ' ' . self::i18n( 'times' );
  }

  static function ordinal ( $number ) {
    if (( $number % 100 ) >= 11 && ( $number % 100 ) <= 13 ) return $number . 'th';
    $ends = array( 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' );
    return $number . $ends[ $number % 10 ];
  }

  /**
   * Return the relationship between this person and another, brief form
   */
  function _relationship( $person ) {

    foreach ( array( 'father', 'mother' ) as $parent ) {
      if ( $this->id( ) == $person->$parent( )->id( )) return self::i18n( $parent );
    }

    if ( $this->id( ) == $person->father( )->father( )->id( )) return self::i18n( 'paternal grandfather' );
    if ( $this->id( ) == $person->father( )->mother( )->id( )) return self::i18n( 'paternal grandmother' );
    if ( $this->id( ) == $person->mother( )->father( )->id( )) return self::i18n( 'maternal grandfather' );
    if ( $this->id( ) == $person->mother( )->mother( )->id( )) return self::i18n( 'maternal grandmother' );

    if ( $this->father( )->id( ) == $person->id( )) return $this->childType( );
    if ( $this->mother( )->id( ) == $person->id( )) return $this->childType( );

    if ( $this->father( )->father( )->id( ) == $person->id( )) return $this->grandChildType( );
    if ( $this->father( )->mother( )->id( ) == $person->id( )) return $this->grandChildType( );
    if ( $this->mother( )->father( )->id( ) == $person->id( )) return $this->grandChildType( );
    if ( $this->mother( )->mother( )->id( ) == $person->id( )) return $this->grandChildType( );

    if ( in_array( $person->id( ), $this->ancestorIDs( ))) {
      if ( $level = $this->hasAncestor( $person, 1 )) {
        if ( $level == 1 ) return $this->greatGrandChildType( );
        return $level . 'x ' . $this->greatGrandChildType( );
      }
      return self::i18n( 'descendent' );
    }
    if ( in_array( $this->id( ), $person->ancestorIDs( ))) {
      if ( $level = $person->hasAncestor( $this, 1 )) {
        if ( $level == 1 ) return $this->greatGrandParentType( );
        return $level . 'x ' . $this->greatGrandParentType( );
      }
      return self::i18n( 'ancestor' );
    }
    if ( array_intersect( $this->parentIDs( ), $person->parentIDs( ))) return $this->siblingType( );
    for ( $i = 2; $i < self::$generationsToName; $i ++ ) {
      if ( array_intersect( $this->parentIDs( $i ), $person->parentIDs( $i ))) return self::ordinal( $i - 1 ) . ' ' . self::i18n( 'cousin' );
    }

    if ( array_intersect( $this->parentIDs( 2 ), $person->parentIDs( ))) return $this->siblingChildType( );
    /* for ( $i = 3; $i < self::$generationsToName; $i ++ ) {
      if ( array_intersect( $this->parentIDs( $i ), $person->parentIDs( ))) return ( $i - 3 ) . 'x ' self::i18n( 'great' ) . ' ' . $this->siblingChildType( );
    } */
    if ( array_intersect( $this->parentIDs( ), $person->parentIDs( 2 ))) return $this->parentSiblingType( );
    /* for ( $i = 3; $i < self::$generationsToName; $i ++ ) {
      if ( array_intersect( $this->parentIDs( $i ), $person->parentIDs( ))) return ( $i - 3 ) . 'x ' . self::i18n( 'great' ) . ' ' . $this->parentSiblingType( );
    } */

    for ( $i = 2; $i < self::$generationsToName; $i ++ ) {
      for ( $j = 2; $j < self::$generationsToName; $j ++ ) {
        if ( $i == $j ) continue; // already tested this
        if ( array_intersect( $this->parentIDs( $i ), $person->parentIDs( $j ))) return self::ordinal( $i - 1 ) . ' ' . self::i18n( 'cousin' ) . ' ' . self::commodore( abs( $i - $j )) . ' ' . self::i18n( 'removed' ) . '?';
      }
    }
    if ( array_intersect( $this->ancestorIDs( ), $person->ancestorIDs( ))) return self::i18n( 'related' );

    return null;
  }

  function __toString ( ) {
    $parts = array( );
    if ( ! $this->isPrivate( )) {
      $occupation = $this->occupation( );
      if ( $occupation ) array_push( $parts, $occupation );
      foreach ( array( 'BIRT', 'BAPM', 'DEAT', 'BURI' ) as $tag ) {
        $content = $this->timeAndPlace( $tag, true );
        if ( $content ) array_push( $parts, $content );
      }
      $notes = $this->notes( );
      if ( $notes ) array_push( $parts, $notes );
    }
    array_push( $parts, '</p>' . $this->tableTree( ! $this->isPrivate( )) . '<p>' ); // @todo dreadful html
    if ( $this->isPrivate( )) {
      array_push( $parts, 'Respecting the privacy of ' . $this->name( ) .' (at least partly!). If you are ' . $this->name( ) . ' and you would like more of your details removed from this site please get in touch. Likewise if you can offer more details of your family tree, please also drop me a line!' ); // @todo i18n
    }
    array_push( $parts, ucfirst( self::i18n( 'father' )) . ' ' . $this->father( )->name( false, ! $this->father( )->isPrivate( )));
    array_push( $parts, ucfirst( self::i18n( 'mother' )) . ' ' . $this->mother( )->name( false, ! $this->mother( )->isPrivate( )));
    foreach ( $this->spouses( ) as $spouse ) {
      array_push( $parts, ucfirst( self::i18n( 'spouse' )) . ' ' . $spouse->name( true ));
    }
    $siblings = array_map( function( $sibling ) { return $sibling->name( true ); }, $this->siblings( ));
    if ( count( $siblings )) array_push( $parts, ucfirst( self::i18n( 'siblings' )) . ': ' . implode( ', ', $siblings ));
    return implode( "\n\n", $parts );
  }

}

?>
