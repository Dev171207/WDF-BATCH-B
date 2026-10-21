var events = [];

var currentPage = 1;

var perPage = 3;


fetch("event.json")
.then(function(response)
{
    if(!response.ok)
    {
        throw new Error("JSON file not found");
    }

    return response.json();
})
.then(function(data)
{
    events = data;

    showEvents();
})
.catch(function(error)
{
    document.getElementById("events").innerHTML =
        "<p>Unable to load events.json</p>";

    console.log(error);
});


function showEvents()
{
    var search =
        document.getElementById("search").value.toLowerCase();

    var filter =
        document.getElementById("filter").value;

    var sort =
        document.getElementById("sort").value;


    var result = events.filter(function(event)
    {
        var searchMatch =
            event.name.toLowerCase().includes(search);

        var filterMatch =
            filter == "All" ||
            event.category == filter;

        return searchMatch && filterMatch;
    });


    if(sort == "name")
    {
        result.sort(function(a, b)
        {
            return a.name.localeCompare(b.name);
        });
    }


    if(sort == "date")
    {
        result.sort(function(a, b)
        {
            return a.date.localeCompare(b.date);
        });
    }


    var start =
        (currentPage - 1) * perPage;

    var end =
        start + perPage;


    var pageEvents =
        result.slice(start, end);


    var output = "";


    pageEvents.forEach(function(event)
    {
        output +=
        "<div class='event'>" +

        "<h2>" + event.name + "</h2>" +

        "<p>Date: " + event.date + "</p>" +

        "<p>Category: " + event.category + "</p>" +

        "<p>Venue: " + event.venue + "</p>" +

        "</div>";
    });


    if(output == "")
    {
        output =
            "<p>No events are currently available.</p>";
    }


    document.getElementById("events").innerHTML =
        output;


    document.getElementById("pageNumber").innerHTML =
        "Page " + currentPage;
}


function nextPage()
{
    var totalPages =
        Math.ceil(events.length / perPage);

    if(currentPage < totalPages)
    {
        currentPage++;

        showEvents();
    }
}


function previousPage()
{
    if(currentPage > 1)
    {
        currentPage--;

        showEvents();
    }
}