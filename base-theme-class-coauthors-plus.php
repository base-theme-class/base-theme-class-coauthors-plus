<?php
/*
+----------------------------------------------------------------------
| Copyright (c) 2018,2019,2020 Genome Research Ltd.
| This is part of the Wellcome Sanger Institute extensions to
| wordpress.
+----------------------------------------------------------------------
| This extension to Worpdress is free software: you can redistribute
| it and/or modify it under the terms of the GNU Lesser General Public
| License as published by the Free Software Foundation; either version
| 3 of the License, or (at your option) any later version.
|
| This program is distributed in the hope that it will be useful, but
| WITHOUT ANY WARRANTY; without even the implied warranty of
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
| Lesser General Public License for more details.
|
| You should have received a copy of the GNU Lesser General Public
| License along with this program. If not, see:
|     <http://www.gnu.org/licenses/>.
+----------------------------------------------------------------------

# Support functions to make ACF managed pages easier to render..
# This is a very simple class which defines templates {and an
# associated template language which can then be used to render
# page content... more easily...}
#
# See foot of file for documentation on use...
#
# Author         : js5
# Maintainer     : js5
# Created        : 2018-02-09
# Last modified  : 2018-02-12

 * @package   BaseThemeClass/CoAuthorPlus
 * @author    JamesSmith james@jamessmith.me.uk
 * @license   GLPL-3.0+
 * @link      https://jamessmith.me.uk/base-theme-class/
 * @copyright 2018 James Smith
 *
 * @wordpress-plugin
 * Plugin Name: Website Base Theme Class - Co-Author plus support
 * Plugin URI:  https://jamessmith.me.uk/base-theme-class/
 * Description: Support functions to: manage/configure co-author plus plugin
 * Version:     0.1.0
 * Author:      James Smith
 * Author URI:  https://jamessmith.me.uk
 * Text Domain: base-theme-class-locale
 * License:     GNU Lesser General Public v3
 * License URI: https://www.gnu.org/licenses/lgpl.txt
 * Domain Path: /lang
*/

//======================================================================
//
// Co-author plus configuration
//
//----------------------------------------------------------------------
//
// You will need to install the Co-author plus plugin to make this
// work...
//
//----------------------------------------------------------------------
//
// Along with the configuration for the theme this does three things:
//
// * Enables co-authors plus on all post types (including custom types)
// * Moves the co-authors plus configuration to the bottom of the right
//   hand side navigation panel
//
//   [ These two are added by $this->enable_co_authors_plus_on_all_post_types() ]
//
// * Tweak co-authors plus configuration to allow one of:
//   * Admins can add/remove authors
//   * Owners can add/remove authors
//   * Authors can add/remove authors [ Can steal posts! ]
//
//   [ This functionality is added by calling $this->allow_multiple_authors(),
//     and configured in the web interface with co-authors
//     theme customisation ]
//
//======================================================================


namespace BaseThemeClass;

class CoAuthorsPlus {
  var $self;
  function __construct( $self ) {
    $this->self = $self;
    $this->enable_co_authors_plus_on_all_post_types()
         ->restrict_who_can_manage_authors();
  }

  function enable_co_authors_plus_on_all_post_types() {
    // Now get the custom_post types we generated and attach co-authors to them!
    add_filter( 'coauthors_supported_post_types', function( $post_types ) { return array_merge( $post_types, array_keys($this->self->custom_types) ); } );
    // The following two lines place the co-author box on the right hand side
    // After the main page "meta-data" publish box...
    add_filter( 'coauthors_meta_box_context',     function() { return 'side'; } ); // Move to right hand side
    add_filter( 'coauthors_meta_box_priority',    function() { return 'low';  } ); // Place under other boxes
    return $this;
  }

  // Wrapper around co-authors to allow authors to add other authors...
  function restrict_who_can_manage_authors() {  // This is the default one - let the owner (first author change authors)
    // Enable admin interface to allow choice...
    add_action( 'customize_register',              [ $this, 'co_authors_theme_params' ] );
    $flag = get_theme_mod('coauthor_options');
    switch( $flag ) {
      case 'owner':
        add_filter( 'coauthors_plus_edit_authors', [ $this, 'let_owner_add_other_authors' ] );
        break;
      case 'author':
        add_filter( 'coauthors_plus_edit_authors', [ $this, 'let_author_add_other_authors' ] );
        break;
    }
    return $this;
  }

  function co_authors_theme_params( $wp_customizer ) {
    $this->self->_create_custom_theme_params( $wp_customizer, 'base-theme-class', 'Base theme class settings', [
      'coauthor_options' => [
        'type'        => 'radio',
        'choices'     => [ 'admin' => 'Administrator', 'owner' => 'Owner', 'author' => 'Author' ],
        'description' => 'Adding authors is restricted to',
        'default'     => 'admin'
      ],
    ] );
  }

  function let_owner_add_other_authors( $can_set_authors ) {
    $f = $can_set_authors || ( get_post() && wp_get_current_user()->ID == get_post()->post_author );
    return $f;
  }

  function let_author_add_other_authors( $can_set_authors ) {
    if( $can_set_authors ) return true; // We know that the person can edit so return true;
    if( ! get_post() ) {
      return false;
    }
    $user_id   = wp_get_current_user()->ID;
    foreach( get_coauthors( get_post()->ID ) as $auth )  {
      if( $auth->ID == $user_id ) return true;
    }
    return false;
  }
}
