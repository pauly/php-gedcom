<?php

class Person {

  var $_data;
  var $_gedcom;
  var $_father;
  var $_mother;
  var $_siblings;
  var $_children;
  var $_spouses;

  function __construct ( $data = array( ), $gedcom = array( )) {
    $this->_data = $data;
    $this->_gedcom = $gedcom;
    if ( is_string( $data )) $this->_data = $this->_gedcom['INDI'][ $this->_id( $data ) ];
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
    return $gedcom;
  }

  function _id ( $data ) {
    if ( ! $data ) return null;
    if ( is_array( $data )) return $this->_id( $data[0] );
    return @preg_replace( '/@/', '', $data );
  }

  function data ( $tag ) {
    if ( ! isset( $this->_data[ $tag ] )) return null;
    return $this->_data[ $tag ];
  }

  function id ( ) {
    return substr( $this->_data['_ID'], 1 );
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
        if ( isset( $this->_gedcom['SOUR'][$id][$tag] )) {
          foreach ( $this->_gedcom['SOUR'][$id][$tag][$tag] as $source ) {
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
      foreach ( $this->_gedcom['NOTE'][$id]['CONC']['CONC'] as $note ) {
        array_push( $notes, trim( $note ));
      }
    }
    return implode( '', $notes );
  }

  function familiesWithParents ( ) {
    if ( ! isset( $this->_data['FAMC'] )) return null;
    $familyID = $this->_id( $this->_data['FAMC']['FAMC'][0] );
    return $this->_gedcom['FAM'][$familyID];
  }

  function familiesWithSpouse ( ) {
    if ( ! isset( $this->_data['FAMS'] )) return array( );
    $families = array( );
    foreach ( $this->_data['FAMS']['FAMS'] as $family ) {
      $familyID = $this->_id( $family );
      array_push( $families, $this->_gedcom['FAM'][$familyID] );
    }
    return $families;
  }

  function mother ( ) {
    if ( ! $this->_mother ) {
      $family = $this->familiesWithParents( );
      $mother = isset( $family['WIFE'] ) ? $family['WIFE']['WIFE'][0] : null;
      $this->_mother = new Person( $mother, $this->_gedcom );
    }
    return $this->_mother;
  }

  function father ( ) {
    if ( ! $this->_father ) {
      $family = $this->familiesWithParents( );
      $father = isset( $family['HUSB'] ) ? $family['HUSB']['HUSB'][0] : null;
      $this->_father = new Person( $father, $this->_gedcom );
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
              $spouse = new Person( $spouse, $this->_gedcom );
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
          $sibling = new Person( $child, $this->_gedcom );
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
            array_push( $this->_children, new Person( $child, $this->_gedcom ));
          }
        }
      }
    }
    return $this->_children;
  }

  function _year ( $type = 'BIRT' ) {
    if ( ! isset( $this->_data[ $type ] )) return '';
    if ( ! isset( $this->_data[ $type ]['DATE'] )) return '';
    if ( preg_match( '/(\d{4})/', $this->_data[ $type ]['DATE'][0], $match )) {
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

  function years ( ) {
    $birth = $this->_year( 'BIRT' );
    $death = $this->_year( 'DEAT' );
    if ( ! ( $birth || $death )) return '';
    return $this->_year( 'BIRT' ) . ' - ' . $this->_year( 'DEAT' );
  }

  function tagToLabel ( $tag ) {
    if ( $tag == 'BIRT' ) return 'Born';
    if ( $tag == 'BAPM' ) return 'Baptised';
    if ( $tag == 'DEAT' ) return 'Died';
    if ( $tag == 'BURI' ) return 'Buried';
    if ( $tag == 'OCCU' ) return 'Occupation';
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

  function tableTree ( ) {
    $html = '<table id="family" summary="' . $this->name( ) . ' family tree">';
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
    $html .= '<td class="self" colspan="' . $c * 8 . '">';
    $html .= $this->name( );
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

  function ancestors ( ) {
    $ancestors = array( );
    if ( $this->father( )->id( )) {
      array_push( $ancestors, $this->father( )->id( ));
      $ancestors = array_merge( $ancestors, $this->father( )->ancestors( ));
    }
    if ( $this->mother( )->id( )) {
      array_push( $ancestors, $this->mother( )->id( ));
      $ancestors = array_merge( $ancestors, $this->mother( )->ancestors( ));
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
   * Return the relationship between this person and another, brief form
   */
  function _relationship( $person ) {
    if ( $this->id( ) == $person->id( )) return 'themself';

    if ( $this->id( ) == $person->father( )->id( )) return 'father';
    if ( $this->id( ) == $person->mother( )->id( )) return 'mother';

    if ( $this->id( ) == $person->father( )->father( )->id( )) return 'paternal grandfather';
    if ( $this->id( ) == $person->father( )->mother( )->id( )) return 'paternal grandmother';
    if ( $this->id( ) == $person->mother( )->father( )->id( )) return 'maternal grandfather';
    if ( $this->id( ) == $person->mother( )->mother( )->id( )) return 'maternal grandmother';

    if ( $this->father( )->id( ) == $person->id( )) return 'child';
    if ( $this->mother( )->id( ) == $person->id( )) return 'child';

    if ( $this->father( )->father( )->id( ) == $person->id( )) return 'grandchild';
    if ( $this->father( )->mother( )->id( ) == $person->id( )) return 'grandchild';
    if ( $this->mother( )->father( )->id( ) == $person->id( )) return 'grandchild';
    if ( $this->mother( )->mother( )->id( ) == $person->id( )) return 'grandchild';

    if ( in_array( $person->id( ), $this->ancestors( ))) {
      if ( $level = $this->hasAncestor( $person, 1, $this->id( ) == 389 )) {
        // error_log( $this->name( ) . ' is ' . ( $level - 2 ) . 'x grandchild' );
        if ( $level == 1 ) return 'great grandchild';
        return $level . 'x great grandchild';
      }
      return 'descendent';
    }
    if ( in_array( $this->id( ), $person->ancestors( ))) {
      // error_log( $person->name( ) . ' has ancestor ' . $this->name( ));
      if ( $level = $person->hasAncestor( $this, 1, $this->id( ) == 198 )) {
        error_log( $this->name( ) . ' is ' . $level . 'x great grandparent' );
        if ( $level == 1 ) return 'great grandparent';
        return $level . 'x great grandparent (possibly, still working on the logic here)';
      }
      return 'ancestor';
    }
    if ( array_intersect( $this->ancestors( ), $person->ancestors( ))) return 'related';

    return null;
  }

  function __toString ( ) {
    $parts = array( );
    if ( ! $this->isPrivate( )) {
      $occupation = $this->occupation( );
      if ( $occupation ) array_push( $parts, $occupation );
      foreach ( array( 'BIRT', 'BAPM', 'DEAT', 'BURI' ) as $tag ) {
        $content = $this->timeAndPlace( $tag );
        if ( $content ) array_push( $parts, $content );
      }
      $notes = $this->notes( );
      if ( $notes ) array_push( $parts, $notes );
    }
    array_push( $parts, '</p>' . $this->tableTree( ) . '<p>' );
    if ( $this->isPrivate( )) {
      array_push( $parts, 'Respecting the privacy of ' . $this->name( ) .' (at least partly!). If you are ' . $this->name( ) . ' and you would like more of your details removed from this site please get in touch. Likewise if you can offer more details of your family tree, please also drop me a line!' );
    }
    array_push( $parts, 'Father: ' . $this->father( )->name( false, ! $this->father( )->isPrivate( )));
    array_push( $parts, 'Mother ' . $this->mother( )->name( false, ! $this->mother( )->isPrivate( )));
    foreach ( $this->spouses( ) as $spouse ) {
      array_push( $parts, 'Spouse: ' . $spouse->name( true ));
    }
    $siblings = array_map( function( $sibling ) { return $sibling->name( true ); }, $this->siblings( ));
    if ( count( $siblings )) array_push( $parts, 'Siblings: ' . implode( ', ', $siblings ));
    return implode( "\n\n", $parts );
  }

}

?>
