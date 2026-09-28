function validateForm()
{
    var name = document.getElementById("name").value;
    var email = document.getElementById("email").value;
    var mobile = document.getElementById("mobile").value;
    var password = document.getElementById("password").value;
    var confirm = document.getElementById("confirmPassword").value;
    var course = document.getElementById("course").value;
    var year = document.getElementById("year").value;
    var terms = document.getElementById("terms").checked;


    var namePattern = /^[A-Za-z ]+$/;
    var emailPattern = /^[A-Za-z0-9]+@[A-Za-z]+\.[A-Za-z]+$/;
    var mobilePattern = /^[0-9]{10}$/;
    var passwordPattern = /^(?=.*[A-Z])(?=.*[0-9]).{8,}$/;


    if(name == "")
    {
        alert("Enter your name");
        return false;
    }

    if(!namePattern.test(name))
    {
        alert("Enter a valid name");
        return false;
    }


    if(email == "")
    {
        alert("Enter your email");
        return false;
    }

    if(!emailPattern.test(email))
    {
        alert("Enter a valid email");
        return false;
    }


    if(!mobilePattern.test(mobile))
    {
        alert("Enter a valid 10 digit mobile number");
        return false;
    }


    if(!passwordPattern.test(password))
    {
        alert("Password must have 8 characters, one capital letter and one number");
        return false;
    }


    if(password != confirm)
    {
        alert("Passwords do not match");
        return false;
    }


    if(course == "")
    {
        alert("Select your course");
        return false;
    }


    if(year == "")
    {
        alert("Select your year");
        return false;
    }


    if(document.querySelector('input[name="gender"]:checked') == null)
    {
        alert("Select your gender");
        return false;
    }


    if(terms == false)
    {
        alert("Accept the terms and conditions");
        return false;
    }


    alert("Registration successful!");

    return true;
}