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


describe("coauthor sortable", function() {

  beforeEach(function() {

    jasmine.getStyleFixtures().fixturesPath = 'tests/jasmine/fixtures';
    loadStyleFixtures('admin-ui-style.css');

    jasmine.getFixtures().fixturesPath = 'tests/jasmine/fixtures';
    loadFixtures('sortable-list.html', 'selector-modal.html');

    coauthorsSortable.list = $('#coauthors-select-list');
    coauthorsSortable.toggle = $('#coauthor-add-toggle');
    coauthorsSortable.init();
    coauthorsSelector.init();

  });

  it("should remove an item on clicking its Remove link", function() {
    var postVals = serializeAsPHP( $('#post') );
    expect( postVals['coauthors'].length ).toBe(5);
    expect( postVals['coauthors'] ).toContain('carol|||contributor');

    $('#coauthor-item-carol').find('a.remove-coauthor').trigger('click');

    var postVals = serializeAsPHP( $('#post') );
    expect( postVals['coauthors'].length ).toBe(4);
    expect( postVals['coauthors'] ).not.toContain('carol|||contributor');
  });

  /*
   // I'm going to assume this interaction is tested in jquery-ui-sortable core.
   // Can't think of a useful way of testing it.

  it("should reorder inputs on dragging and dropping a sortable", function() {
  });
  */

  it("should open the Add Coauthor modal on clicking the Add New Link", function() {
    expect( $('#coauthor-select-wrap') ).not.toBeVisible();

    $('#coauthor-add-toggle').trigger('click');

    expect( $('#coauthor-select-wrap') ).toBeVisible();
    expect( $( '#coauthor-select-header' ) ).toContainText( 'Add new author to post' );
  });

  it("should open the Edit Coauthor modal on clicking the link around the current role", function() {
    expect( $('#coauthor-select-wrap') ).not.toBeVisible();

    $('#coauthor-item-carol').find('a.edit-coauthor').trigger('click');

    expect( $('#coauthor-select-wrap') ).toBeVisible();
    expect( $( '#coauthor-select-header' ) ).toContainText( 'Edit author on post' );
  });

});

