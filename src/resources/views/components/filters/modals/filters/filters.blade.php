<div class="modal fade" tabindex="-1" role="dialog" id="filters-modal" aria-modal="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-status bg-success"></div>
      <div class="modal-header">
        <h4 class="modal-title">Filters</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="card" data-type="ruleset">
          <div class="card-header">
              <div class="row row-cols-auto g-3 align-items-center">
                  <div class="col-12">
                      <div class="input-group">
                          <div class="input-group-text">Match Kind</div>
                          <select class="form-select match-kind">
                              <option value="and">All</option>
                              <option value="or">Any</option>
                          </select>
                      </div>
                  </div>
              </div>
          </div>
          <div class="card-body">
              <div class="row row-cards"></div>
          </div>
          <div class="card-footer">
            <div class="btn-group d-flex">
              <button class="btn btn-light btn-rule d-sm-inline-block">
                <i class="fas fa-filter"></i> Add Rule
              </button>
              <button class="btn btn-light btn-ruleset d-sm-inline-block">
                <i class="fas fa-clone"></i> Add Group
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
          <button type="button" data-bs-dismiss="modal" class="btn d-sm-inline-block me-auto">
              <i class="fas fa-times"></i> Cancel
          </button>
          <button type="button" class="btn btn-success d-sm-inline-block">
              <i class="fas fa-check"></i> Update
          </button>
      </div>
    </div>
  </div>
</div>

{{-- JS templates --}}
@include('web::components.filters.modals.filters.rule')
@include('web::components.filters.modals.filters.ruleset')

@push('javascript')
  <script>
    function isUrl(s) {
      let regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
      return regexp.test(s);
    }

    function buildFilters(source) {
        let filters = {};
        (Array.isArray(source) ? source : [source]).forEach(ruleset => {
            let match = ruleset.querySelector('.match-kind');
            let rules = ruleset.querySelectorAll(':scope > .card-body > .row-cards > [data-type="rule"], :scope > .card > .card-body > .row-cards > [data-type="rule"], :scope > .card-body > .row-cards > [data-type="ruleset"]');
            filters[match.value] = [];
            rules.forEach(rule => {
                switch (rule.dataset.type) {
                    case 'rule':
                        let ruleTypeField = rule.querySelector('.rule-type');
                        let ruleTypeActive = ruleTypeField.selectedOptions[0];
                        let ruleOperator = rule.querySelector('.rule-operator');
                        let ruleCriteria = rule.querySelector('.rule-criteria');

                        filters[match.value].push({
                            name: ruleTypeField.value,
                            path: ruleTypeActive.dataset.path,
                            field: ruleTypeActive.dataset.field,
                            operator: ruleOperator.value,
                            criteria: ruleCriteria.value,
                            text: ruleCriteria.selectedOptions[0].text,
                        });
                        break;
                    case 'ruleset':
                        filters[match.value].push(buildFilters([rule]));
                        break;
                }
            });
        });
        return filters;
    }

    function makeSelectorField(target, linkedToElement) {
        return new TomSelect(target, {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            copyClassesToDropdown: false,
            dropdownClass: 'dropdown-menu',
            optionClass:'dropdown-item',
            maxItems: 1,
            preload: true,
            persist: false,
            openOnFocus: true,
            loadThrottle: null,
            dropdownParent: document.getElementById('filters-modal').querySelector('.modal-content'),
            shouldLoad: function (query) {
                return query.length > 0;
            },
            load: function (query, callback) {
                this.clearOptions();
                let dataSource = linkedToElement.selectedOptions[0].dataset.src;
                isUrl(dataSource) ?
                    fetch(dataSource)
                        .then(response => response.json())
                        .then(json => { callback(json.results); })
                        .catch(() => { callback(); })
                    : callback(JSON.parse(dataSource));
            },
        });
    }

    function makeRuleContainer(target, ruleObject) {
        let newRuleNode = document.getElementById('rule-template').cloneNode(true);
        newRuleNode.removeAttribute('id');
        newRuleNode.classList.remove('d-none');
        newRuleNode.querySelector('.rule-operator').value = ruleObject.operator;

        let type = newRuleNode.querySelector('.rule-type');
        let criteria = newRuleNode.querySelector('.rule-criteria');
        type.value = ruleObject.name;

        // init a new selector field on criteria field attached to type field
        makeSelectorField(criteria, type);

        criteria.tomselect.addOption({
            id: ruleObject.criteria,
            text: ruleObject.text,
        });
        criteria.tomselect.setValue(ruleObject.criteria);
        criteria.tomselect.setTextboxValue(ruleObject.text);

        target.appendChild(newRuleNode);
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('.rule-type')) {
            let selectorField = e.target.closest('.row').querySelector('select.rule-criteria');
            selectorField.tomselect.clear();
            selectorField.tomselect.clearOptions();
            selectorField.tomselect.load();
            selectorField.tomselect.render();
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('button[data-dismiss="rule"], button[data-dismiss="ruleset"]')) {
            let targetType = e.target.closest('button').dataset.dismiss;
            let filterBlock = e.target.closest(`[data-type="${ targetType }"]`);
            filterBlock.parentNode.removeChild(filterBlock);
        }

        if (e.target.closest('.btn-ruleset')) {
            let group = document.getElementById('ruleset-template').cloneNode(true);
            group.removeAttribute('id');
            group.classList.remove('d-none');
            e.target.closest('.card').querySelector('.card-body > .row-cards').appendChild(group);
        }

        if (e.target.closest('.btn-rule')) {
            let rule = document.getElementById('rule-template').cloneNode(true);
            rule.removeAttribute('id');
            rule.classList.remove('d-none');

            // init a new selector field on criteria field attached to type field
            makeSelectorField(rule.querySelector('.rule-criteria'), rule.querySelector('.rule-type'));

            e.target.closest('.card').querySelector('.card-body > .row-cards').appendChild(rule);
        }

        if (e.target.closest('#filters-modal .btn-success')) {
            document.getElementById('filters-btn').dataset.filters =
                JSON.stringify(buildFilters(document.querySelector('#filters-modal .modal-body [data-type="ruleset"]')));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('filters-modal')).toggle();
        }

        if (e.target.matches('button[data-bs-dismiss="card"]')) {
            let filtersContainer = e.target.closest('.card');
            filtersContainer.parentNode.removeChild(filtersContainer);
        }
    });

    document.addEventListener('show.bs.modal', function (e) {
        if (e.target.matches('#filters-modal')) {
            if (! e.relatedTarget.dataset.filters || e.relatedTarget.dataset.filters === '{}') return;

            // extract rules JSON object from the modal trigger (button)
            // retrieve the modal itself and clear its content from any remaining nodes
            let rules = JSON.parse(e.relatedTarget.dataset.filters);
            let filtersContainer = document.querySelector('#filters-modal .modal-body > .card > .card-body > .row-cards');
            while (filtersContainer.firstChild) filtersContainer.removeChild(filtersContainer.firstChild);

            // init the container with global match-kind value (AND / OR operator)
            document.querySelector('#filters-modal .modal-body > .card > .card-header .match-kind').value = rules.hasOwnProperty('and') ? 'and' : 'or';
            // update rules variable with global rule's container (AND / OR operator)
            rules = rules.hasOwnProperty('and') ? rules.and : rules.or;
            if (! rules) return;

            // loop over each rule
            // when the rule is a wrapper, spawn a new ruleset container
            // otherwise, spawn a new rule container
            rules.forEach((rule) => {
                if (rule.hasOwnProperty('name')) {
                    makeRuleContainer(filtersContainer, rule);
                }

                if (rule.hasOwnProperty('and') || rule.hasOwnProperty('or')) {
                    let ruleset_rules = rule.hasOwnProperty('and') ? rule.and : rule.or;
                    let ruleset = document.getElementById('ruleset-template').cloneNode(true);
                    ruleset.removeAttribute('id');
                    ruleset.classList.remove('d-none');
                    ruleset.querySelector('.match-kind').value = rule.hasOwnProperty('and') ? 'and': 'or';

                    if (ruleset_rules) {
                        ruleset_rules.forEach((ruleset_rule) => {
                            makeRuleContainer(ruleset.querySelector('.card-body .row-cards'), ruleset_rule);
                        });
                    }

                    filtersContainer.appendChild(ruleset);
                }
            });
        }
    });
  </script>
@endpush
