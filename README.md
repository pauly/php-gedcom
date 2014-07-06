php-gedcom
==========

I wanted some simple php code for parsing a gedcom file to integrate the family tree with my existing website, and this is it.

Most of code that came up on php gedcom searches were all encompassing site generators, overkill for me, [I already have a site](http://www.clarkeology.com) and I just wanted to integrate the gedcom family tree data with it.

It's not perfect, pull requests appreciated. Every object carries a copy of the whole gedcom file for working out relationships, not sure about that one. It might not scale well.

Usage:

require_once 'Person.php';
$gedcom = Person::parse( './my-tree.ged' );

foreach ( $gedcom['INDI'] as $person ) {
  $person = new Person( $person, $gedcom );
  echo $person->surname( ) . ', ' . $person->forename( ) . ' ' . $person->years( ) . "\n";
  echo $person->link( ) . "\n";
  // echo $person->name( ) . ' father is ' . $person->father->( )->name( );
  // echo $person->name( ) . ' maternal grandfather is ' . $gedcom['INDI']['I1']->mother->( )->father( )->name( );
}
