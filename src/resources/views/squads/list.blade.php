@extends('web::layouts.grids.12')

@section('title', trans_choice('web::squads.squad', 0))
@section('page_header', trans_choice('web::squads.squad', 0))
@section('page_description', trans('web::seat.list'))

@section('full')
  <form onsubmit="return false;">
    <div class="form-row align-items-center mb-3">
      <div class="col">
        <input name="search-squad" type="text" class="form-control" placeholder="Search" />
      </div>
      <div class="col">
        <div class="btn-group d-flex">
          @can('squads.create')
            <a href="{{ route('seatcore::squads.create') }}" class="btn btn-square btn-light d-sm-inline-block">
              <i class="fas fa-plus"></i>
              Create
            </a>
          @endcan
          <button data-filter-field="type" data-filter-value="manual" type="button" class="btn btn-square btn-success d-sm-inline-block deck-filters active">
            <i class="fas fa-check-circle"></i>
            Manual
          </button>
          <button data-filter-field="type" data-filter-value="auto" type="button" class="btn btn-square btn-info d-sm-inline-block deck-filters active">
            <i class="fas fa-check-circle"></i>
            Auto
          </button>
          <button data-filter-field="type" data-filter-value="hidden" type="button" class="btn btn-square btn-dark d-sm-inline-block deck-filters active">
            <i class="fas fa-check-circle"></i>
            Hidden
          </button>
          <button data-filter-field="candidates" data-filter-value="{{ auth()->user()->id }}" type="button" class="btn btn-square btn-primary d-sm-inline-block deck-filters">
            Candidate
          </button>
          <button data-filter-field="members" data-filter-value="{{ auth()->user()->id }}" type="button" class="btn btn-square btn-secondary d-sm-inline-block deck-filters">
            Member
          </button>
          <button data-filter-field="moderators" data-filter-value="{{ auth()->user()->id }}" type="button" class="btn btn-square btn-warning d-sm-inline-block deck-filters">
            Moderator
          </button>
          <button data-filter-field="is_moderated" data-filter-value="true" type="button" class="btn btn-square btn-danger d-sm-inline-block deck-filters">
            Moderated Only
          </button>
        </div>
      </div>
    </div>
  </form>

  <div id="squad-deck"></div>

  <nav>
    <ul class="pagination justify-content-center" id="pagination-squad"></ul>
  </nav>

@endsection

@push('javascript')
  <script>
      const chunk = (arr, size) =>
          Array.from({ length: Math.ceil(arr.length / size) }, (v, i) =>
              arr.slice(i * size, i * size + size)
          );

      const card_factory = (data) => `<div class="col mb-4">
                                        <div class="card h-100">
                                            <img src="${data.logo}" alt="${data.name}" width="128" class="card-img-top" />
                                            <div class="card-body pb-0">
                                                <h5 class="card-title">${data.name}</h5>
                                                <p class="card-text">${data.summary}</p>
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item">
                                                        ${data.is_moderated ? '<i class="fas fa-check text-success me-3"></i>' : '<i class="fas fa-times text-danger me-3"></i>'}
                                                        Moderated
                                                    </li>
                                                    <li class="list-group-item">
                                                        ${data.is_moderator ? '<i class="fas fa-check text-success me-3"></i>' : '<i class="fas fa-times text-danger me-3"></i>'}
                                                        Moderator
                                                    </li>
                                                    <li class="list-group-item">
                                                        ${data.is_member ? '<i class="fas fa-check text-success me-3"></i>' : '<i class="fas fa-times text-danger me-3"></i>'}
                                                        Member
                                                    </li>
                                                    <li class="list-group-item">
                                                        ${data.type === 'manual' ? (data.is_candidate ? '<i class="fas fa-check text-success me-3"></i> Candidate' : '<i class="fas fa-times text-danger me-3"></i> Candidate') : ''}
                                                    </li>
                                                </ul>
                                                <div class="d-flex justify-content-around my-3">
                                                    ${data.type === 'manual' ?
                                                    `<span class="badge badge-pill bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Members">${data.members_count}</span>
                                                    <span class="badge badge-pill bg-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="Moderators">${data.moderators_count}</span>
                                                    <span class="badge badge-pill bg-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Candidates">${data.applications_count}</span>`
                                                    :
                                                   `<span class="badge badge-pill bg-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="Members">${data.members_count}</span>
                                                    <span class="badge badge-pill bg-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="Moderators">${data.moderators_count}</span>`
                                                    }
                                                </div>
                                                <a href="${data.link}" class="btn btn-link stretched-link"></a>
                                            </div>
                                            <div class="card-footer">
                                                <span class="badge ${data.type === 'hidden' ? 'bg-dark' : data.type === 'auto' ? 'bg-info' : 'bg-success' }">${data.type.substr(0, 1).toUpperCase() + data.type.substr(1).toLowerCase()}</span>
                                            </div>
                                        </div>
                                    </div>`;

      const pagination_first_page_factory = (data) => `<li class="page-item ${data.current_page === 1 ? 'disabled' : ''}" ${data.current_page === 1 ? 'aria-disabled="true"' : ''} aria-label="Previous">
                                                        ${data.current_page === 1 ?
                                                          '<span class="page-link" aria-hidden="true">&laquo;</span>' :
                                                          '<a data-page="1" class="page-link" href="' + data.first_page_url + '" rel="previous" aria-label="Previous">&laquo;</a>'
                                                         }
                                                       </li>`;

      const pagination_last_page_factory = (data) => `<li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}" ${data.current_page === data.last_page ? 'aria-disabled="true"' : ''} aria-label="Next">
                                                        ${data.current_page === data.last_page ?
                                                            '<span class="page-link" aria-hidden="true">&raquo;</span>' :
                                                            '<a data-page="' + data.last_page + '" class="page-link" href="' + data.last_page_url + '" rel="next" aria-label="Next">&raquo;</a>'
                                                        }
                                                       </li>`;

      const pagination_page_factory = (data, page) => `<li class="page-item ${data.current_page === page ? 'active': ''}" ${data.current_page === page ? 'aria-current="page"' : ''}>
                                                        ${data.current_page === page ?
                                                            '<span class="page-link">' + page + '</span>' :
                                                            '<a data-page="' + page +'" class="page-link" href="' + data.path + '?page=' + page + '">' + page + '</a>'
                                                        }
                                                       </li>`;

      function searchHandlerDelay(callback, ms) {
          let timer = 0;
          return function (...args) {
              clearTimeout(timer);
              timer = setTimeout(callback.bind(this, ...args), ms || 0);
          };
      }

      function refreshSquadDeck(keyword, page) {
          var filters = {};
          var endpoint = `${window.location.protocol}//${window.location.hostname}:${window.location.port}${window.location.pathname}`;

          $('.deck-filters.active').each(function (i, e) {
              var a = $(e);
              var f = a.data('filter-field');
              var v = a.data('filter-value');

              if (! filters[f]) filters[f] = [];
              filters[f].push(v);
          });

          $.get(endpoint, {query: keyword, page: page, filters: filters}, function (d) {
              $('#squad-deck').empty();

              chunk(d.data, 6).forEach(function (row) {
                  var squad_row = $('<div class="row row-cols-1 row-cols-md-6">');

                  row.forEach(function (squad) {
                      squad_row.append($(card_factory(squad)).hide().fadeIn('slow'));
                  });

                  if (row.length < 6) {
                      for (var i = 0; i < (6 - row.length); i++)
                          squad_row.append($('<div class="col mb-4">'));
                  }

                  $('#squad-deck').append(squad_row);
              });

              pagination = $('#pagination-squad');
              pagination.empty();

              pagination.append(pagination_first_page_factory(d));

              for (var page = 1; page <= d.last_page; page++)
                  pagination.append(pagination_page_factory(d, page));

              pagination.append(pagination_last_page_factory(d));
          });
      }

      $(document).ready(function () {
          var keyword = $('input[name="search-squad"]').val();
          refreshSquadDeck(keyword, 1);
      });

      $('input[name="search-squad"]').on('keyup', searchHandlerDelay(function () {
          refreshSquadDeck(this.value, 1);
      }, 500));

      $(document)
          .on('click', '.pagination a', function(e) {
              var keyword = $('input[name="search-squad"]').val();
              e.preventDefault();

              $('.pagination li').removeClass('active');

              $(this).parent('li').addClass('active');

              refreshSquadDeck(keyword, $(this).data('page'));
          })
          .on('click', '.deck-filters', function () {
              var keyword = $('input[name="search-squad"]').val();
              $(this).hasClass('active') ? $(this).removeClass('active') : $(this).addClass('active');
              $(this).hasClass('active') ? $(this).prepend('<i class="fas fa-check-circle">') : $(this).find('i').remove();

              refreshSquadDeck(keyword, 1);
          });
  </script>
@endpush
