function checkFeedback()
{
    var name = document.getElementById("name").value;
    var email = document.getElementById("email").value;
    var feedback = document.getElementById("feedback").value;

    if(name == "")
    {
        alert("Please enter your name");
        return false;
    }

    if(email == "")
        {
        alert("Please enter your email");
        return false;
    }

    if(feedback == "")
    {
        alert("Please enter your feedback");
        return false;
    }

    alert("Thank you for your feedback!");
    return true;
}