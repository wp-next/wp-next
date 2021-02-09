<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.4.0/Sortable.min.js"></script>
<style media="screen">
    .heading {
        margin: 30px 0;
    }
    .list li {
        background: #fff;
        padding: 10px;
        cursor: move;
        max-width: 100%;
        box-sizing: border-box;
    }
    #success {
        display: none;
        margin: 10px 0;
        box-sizing: border-box;
    }
    #success.isActive {
        display: block;
    }
</style>

<div class="wrap">
    <h1>
        {{ $label }}
    </h1>

    <div class="message updated fade" id="success">
        <p>
            Sort order updated
        </p>
    </div>

    <ul id="sort-posts" class="list sortable">
        @foreach ($posts as $post)
            <li data-id="{{ $post->ID }}">
                {{ $post->post_title }}
            </li>
        @endforeach
    </ul>

    <button id="savePostsOrder" class="button-primary">
        Save
    </button>
</div>

<script type="text/javascript">
    var button = document.querySelector('#savePostsOrder');
    var list = document.querySelector(".sortable");

    button.addEventListener('click', function() {
        updateSortOrder(list);
    });

    Sortable.create(list, {
        group: "sorting",
        sort: true,
    });

    function updateSortOrder(list) {
        var listItems = list.children;

        itemsToUpdate = [];

        for (var i = 0; i < listItems.length; i++) {
            var id = listItems[i].dataset.id;

            var item = {
                id: id,
                order: i + 1
            };

            itemsToUpdate.push(item);
        }

        jQuery.ajax({
           type: 'POST',
           dataType: 'json',
           url: ajaxurl,
           data: {
               'action': 'updatePostsOrder',
               'posts': itemsToUpdate,
           },
           success: function(result) {
                if (result) {
                    var notice = document.querySelector('#success');
                    notice.classList.add('isActive');
                    window.scrollTo({ top: 0 });
                    setTimeout(function() {
                        notice.classList.remove('isActive');
                    }, 5000);
                }
           }
        });
    }

</script>