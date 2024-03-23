## Code Challenge


Build a simplified banking system

the system has 2 types of users

1. a customer

2. an admin

customer user stories:

- a user can create a new account with username and password

- a user starts with 0 balance

- a user can deposit more money to his account by uploading a picture of a check and entering the amount of the check. if the check is approved by an admin, the money is added to the bank account.
- /**
- * The picture can be of anything, you don't need to parse it or validate it in any way
- */


- to buy something, the user enters the amount and description; a user can only buy something if she has enough money to cover the cost.

- a user can see a list of balance changes including time and description.


admin user stories:

- an admin account is already created with a hard coded username and password.

- an admin can see a list of pending check deposit pictures with amount and picture and click to approve or deny the deposit.


***
Simplifying Assumptions

* an admin can’t be also a customer

------
This is the test https://github.com/TidyDaily/developer-test
Please make sure you use Laravel, React or vue.js in the development.
Also use typescript (if possible) , this will help us to understand your level.
Normally our tests are deployed in heroku to make it easier for us to validate, feel free to use your own environment, just make sure we can access it.
The deliverable is a github repo and a URL to a deployed application.
It would helpful to take a look at the visual description found here
https://github.com/TidyDaily/developer-test/blob/main/Code%20Challenge%20-%20Bank%20System%20Wireframes.pdf , these are mobile screens but youo don't need to do mobile, can be web application.
What do we look at when checking out the solution:

    Functionality: We'll look at the application and verify if everything's working properly
    Performance: We'll analyze your code to see how performing it is, and how well it behaves with the requests being made
    Code quality and organization: We'll check your knowledge to see if your code does what it's meant to be done, if it's easy to understand, if it's testable and follows a consistent baseline throughout the app, as well as it's properly organized
    Framework knowledge: We'll verify your knowledge regarding the chosen framework; If you know how to use its tools properly, if you understand what those tools are meant to do, and how well you applied them, as well as following the framework's rules and patterns;

For the back-end specific requirements:

    Automated Tests / Test Coverage
    API Access Control (Policies, Gates, etc.)

And big extra points if you:

    Implement a design pattern (DDD, Repository Pattern, etc.)
    Make a good and cohesive database design

For the front-end specific requirements:

    TypeScript
    Code structure and organization (How you organize it is a personal choice, but it needs to be well organized and structured, as well as following your choice's framework patterns and guidelines)

And big extra points if you have a solid knowledge of:

    Front-end cache
    Performance and monitoring tools
