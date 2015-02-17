describe("The coauthor selector modal", function() {

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

  /*
  it("should show all authors not already connected to the current post on opening", function() {
  });

  it("should look up search results after entering search string", function() {
  });

  it("should not allow empty entries to be submitted", function() {
  });

  it("should open with current author's info when editing existing coauthor", function() {
  });

  it("should add a new item to the end of the sortable list on adding", function() {
  });

  it("should update sortable item on saving changes to existing coauthor", function() {
  });
  */

});

