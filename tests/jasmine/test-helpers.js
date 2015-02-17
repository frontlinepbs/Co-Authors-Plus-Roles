/**
 * Set some globals that would have been set through
 * wp_localize_script() etc.
 */
var ajaxurl, coauthorsL10n;
ajaxurl = 'tests/jasmine/fixtures/admin-ajax-action';
coauthorsL10n = {
  'title': 'Insert/edit author',
  'update': 'Update',
  'save': 'Add Author',
  'noMatchesFound': 'No results found.',
  'addNewAuthorHeader': 'Add new author to post',
  'editExistingAuthorHeader': 'Edit author on post',
  'addNewAuthorButton': 'Add author to post',
  'editExistingAuthorButton': 'Save changes to author',
};


/**
 * Mocks the way PHP would respond to serialized form data
 *
 */
function serializeAsPHP( form ) {
  var postData, $_REQUEST;

  postData = $(form).serialize().split('&');

  $_REQUEST = _.reduce( postData, function( memo, input ) {
    var splat = decodeURIComponent(input).split('='),
        key = splat[0],
        val = splat[1];

    if (key.indexOf('[]') === key.length-2) {
      key = key.slice(0,-2);
      if ( typeof memo[ key ] === 'undefined' ) {
        memo[ key ] = [];
      }
      memo[ key ].push(val);
    } else {
      memo[ key ] = val;
    }

    return memo;
  }, {});

  return $_REQUEST;
}
