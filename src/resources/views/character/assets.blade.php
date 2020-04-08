@extends('web::character.layouts.view', ['viewname' => 'assets', 'breadcrumb' => trans('web::seat.assets')])

@section('page_header', trans_choice('web::seat.character', 1) . ' ' . trans('web::seat.assets'))

@inject('request', 'Illuminate\Http\Request')

@section('character_content')

  <div class="card card-gray card-outline card-outline-tabs">
    <div class="card-header">
      <h3 class="card-title">Assets</h3>
      <div class="card-tools">
        <div class="input-group input-group-sm">
            <a href="{{ route('tools.jobs.dispatch', ['character_id' => $request->character_id, 'job_name' => 'character.assets']) }}" class="btn btn-sm btn-light">
              <i class="fas fa-sync" data-toggle="tooltip" title="{{ trans('web::seat.update_assets') }}"></i>
            </a>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <select multiple="multiple" id="dt-character-selector" class="form-control" style="width: 100%;">
          @foreach($characters as $character)
            @if($character->character_id == $request->character_id)
              <option selected="selected" value="{{ $character->character_id }}">{{ $character->name }}</option>
            @else
              <option value="{{ $character->character_id }}">{{ $character->name }}</option>
            @endif
          @endforeach
        </select>
      </div>

      <table id="characterTable" class="table compact table-hover table-condensed location-table">
        <thead>
          <tr>
            <th></th>
            <th>{{ trans('web::seat.quantity') }}</th>
            <th>{{ trans_choice('web::seat.type', 1) }}</th>
            <th>{{ trans('web::seat.volume') }}</th>
            <th>{{ trans_choice('web::seat.group',1) }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

@stop

@push('javascript')

  <script type="text/javascript">

    var url = "{{route('character.view.assets.details', ['character_id' => request()->character_id])}}";

    $(document).ready(function() {
        $('#dt-character-selector')
            .select2()
            .on('change', function () {
                assetGroupTable.ajax.reload();
            });
    });

    var assetGroupTable = $('.location-table').DataTable({
      scrollY: '50vh',
      processing: true,
      serverSide: true,
      pageLength: 50,
      ajax: {
        url: url,
        data: function ( d ) {
          d.characters = $("#dt-character-selector").val();
        }
      },
      columns   : [
        {orderable: false, data: null, defaultContent: '', searchable: false},
        {data: 'quantity', name: 'quantity', searchable: false},
        {data: 'item', name: 'item', orderable: false, searchable: false},
        {data: 'volume', name: 'volume', orderable: false, searchable: false},
        {data: 'group', name: 'group', orderable: false, searchable: false},
        {data: 'typeName', name: 'invTypes.typeName', visible: false },
        {data: 'locationName', name: 'locationName', searchable: false, visible: false },
        {data: 'name', name: 'name', visible: false },
      ],
      rowGroup: {
        startRender: function(rows, group) {
          var numberItems = rows.count();
          var volume = rows.data().pluck('type').pluck('volume').reduce(function (a , b) {
            return a + b*1;
          },0);

          return $('<tr/>')
              .append( '<td colspan="5"><b>'+group+'</b><span class="float-right">'+ numberItems +' {{ trans('web::seat.items_taking') }} '+ abbreviateNumber(volume) +' m&sup3</span></td>' )
        },
        dataSrc: 'locationName'
      },
      createdRow: function(row, data, dataIndex) {
        if(data.quantity == null){
          $(row).find("td:eq(0)")
              .addClass('details-control')
              .append('<button class="btn btn-sm btn-link"><i class="fas fa-plus-square"></i></button>');
        }
      },
      drawCallback : function () {
        $("img").unveil(100);
        $("[data-toggle=tooltip]").tooltip();
      }
    });

    assetGroupTable.on('click', 'td.details-control', function () {
      var td = $(this);
      var table = $(td).closest('table');
      var row = $(table).DataTable().row(td.closest('tr'));
      var tr = $(this).closest('tr');
      var symbol = tr.find('i');

      if (row.child.isShown()) {
        // This row is already open - close it
        row.child.hide();
        symbol.removeClass("fa-minus-square").addClass("fa-plus-square");

        tr.removeClass('shown').css("background-color", "");
      } else {
        // Open this row
        symbol.removeClass("fa-plus-square").addClass("fa-minus-square");

        row.child(template(row.data())).show();
        initTable(row.data());

        tr.addClass('shown');
      }
    });

    function template(d) {
      return d.content;
    }

    function initTable(data) {
      $("table#assets-contents[data-item-id=" + data.item_id +"]").DataTable({
        processing: true,
        paging: false,
        info: false,
        searching: false,
        columns: [
          {orderable: false, data: null, defaultContent: '', searchable: false},
          {data: 'quantity', name: 'quantity'},
          {data: 'type', name: 'type', orderable: false, searchable: false},
          {data: 'volume', name: 'volume', orderable: false, searchable: false},
          {data: 'group', name: 'group', orderable: false, searchable: false},
          {data: 'content', name: 'group', orderable: false, searchable: false, visible: false},
        ],
        createdRow: function(row, data, dataIndex) {
          if(data.quantity === ""){
            $(row).find("td:eq(0)")
                .addClass('details-control')
                .attr('data-location-id', data.item_id )
                .attr('data-origin', data.location_id )
                .append('<button class="btn btn-sm btn-link"><i class="fas fa-plus-square"></i></button>');
          }
        },
        drawCallback : function () {
          $("img").unveil(100);
            $("[data-toggle=tooltip]").tooltip();
          // remove additonal created group-rows
          $(".dtrg-group").remove();
        }
      });
    }

  </script>

@endpush