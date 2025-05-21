## Table of contents

- Introduction
- Requirements
- Installation
- Configuration
- Maintainers

## Introduction

This module allows you to add a custom webform element having type Webform Gmap
Field, so this added element generates a google map in the webform frontend and
allows you to select your location on the map which then collects your latlong
coordinates and storing in the webform submission when you submits the webform.


## Requirements

This module requires the following modules:

- [Webform] - https://www.drupal.org/project/webform
- [Simple Google Maps] - https://www.drupal.org/project/simple_gmap


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/
installing-drupal-modules).


## Configuration

1. Google Map API Keys -  In order to display the google map
   (at the webform frontend) you need to add the Google map api keys
   in the webform_gmap_field.libraries.yml file.

Please make sure that you have generated the API keys for the current
sitedomain.

Note - In the next version of this module, we will be adding add the 
functionality to add the google map api keys from the admin settings only.


## Maintainers

- Pravin Gaikwad (Rajeshreeputra) - https://www.drupal.org/u/rajeshreeputra
- Ganesh Suryawanshi (ganeshsurya11) - https://www.drupal.org/u/ganeshsurya11