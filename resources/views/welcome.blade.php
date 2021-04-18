<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KraickList - Find Your Needs Here</title>
</head>

<body>
    <div>
        <form id="form" autocomplete="off">
            <input type="hidden" id="page" name="page" value="0">
            <label for="query">Keyword</label>
            <input type="text" id="query" name="query">
            <br>
            <label for="">Sort by</label>
                <select name="sortBy" id="sortBy">
                    <option value="title">Title</option>
                    <option value="content">Content</option>
                    <option value="updated_at">Last Updated</option>
                </select>
            <br>
            <label for="">Sort Type</label>
                <select name="sortType" id="sortType">
                    <option value="asc">ASC</option>
                    <option value="desc">DESC</option>
                </select>
            <br>
            <button type="submit">Search</button>
        </form>
    </div>
    <div>
        <ul id="resultList"></ul>
        <a href="javascript:void" id="seemore" style="display: none;">See more</a>
    </div>
    <script>
        const Controller = {
            search: (ev) => {
                ev.preventDefault();
                const data = Object.fromEntries(new FormData(form));
                const page = parseInt(data.page);
                const nextPage = page + 1;
                const perpage = 5;
                const nextPerPage = perpage * nextPage;
                const response = fetch(`/list?q=${data.query}&sortBy=${data.sortBy}&sortType=${data.sortType}&page=${nextPage}&perpage=${nextPerPage}`).then((response) => {
                    response.json().then((results) => {
                        if (results.total < 1) {
                            alert(`No result for ${data.query}`);
                            return
                        }
                        Controller.updateList(results.data);

                        document.getElementById("page").value = nextPage;
                        if (results.data.length === results.meta.total) {
                            document.getElementById("seemore").style.display = "none";
                        } else {
                            document.getElementById("seemore").style.display = "block";
                        }
                    });
                });
            },

            updateList: (results) => {
                const rows = [];
                for (let result of results) {
                    rows.push(
                        `
                            <li>
                                <div>
                                    <p>${result.title}</p>
                                    <p>${result.content}</p>
                                </div>
                            </li>
                        `
                    );
                }
                resultList.innerHTML = rows.join(" ");
            },
        };

        const form = document.getElementById("form");
        form.addEventListener("submit", Controller.search);

        const seeMore = document.getElementById("seemore")
        seeMore.addEventListener("click", Controller.search);
    </script>
</body>

</html>
