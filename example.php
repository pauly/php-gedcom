#!/bin/env php
<?php

error_reporting( E_ALL );

if ( ! $argv[1] ) die( 'usage: ' . $argv[0] . ' [gedcom file]' );

require_once __DIR__ . '/Person.php';
$gedcom = Person::parse( $argv[1] );
// now the whole gedcom is in a quirky format in $gedcom, try:
// print_r( $gedcom['INDI']['I1'] );
// echo $gedcom['INDI']['I1']->name( ) . ' father is ' . $gedcom['INDI']['I1']->father->( )->name( );
// echo $gedcom['INDI']['I1']->name( ) . ' maternal grandfateher is ' . $gedcom['INDI']['I1']->mother->( )->father( )->name( );

foreach ( $gedcom['INDI'] as $key => $individual ) {

  $person = new Person( $individual, $gedcom );

  // override the link function - come back to this with a better way, a static method maybe?
  $person->link = function ( ) {
    return 'http://www.clarkeology.com/names/' . $person->_urlise( $person->surname( )) . '/' . $person->id( ) . '/' . $person->_urlise( $person->forename( ));
  };

  echo $person->surname( ) . ', ' . $person->forename( ) . ' ' . $person->years( ) . "\n";
  echo $person->link( ) . "\n";

}
