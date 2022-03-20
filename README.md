<p align="center"><h1>Async DB Helper</p>

<p align="center">
<!-- put badges here -->
</p>

## Introduction

Sometimes it happens that we use  either logger or some special models to log the user data, which is critical to collect but not so much that waste our precious DB connection & resource on it. Like Auditing records for a model.

To help in such scenario, you can use this package. This package stores the records which were meant for DB into redis & moves them to DB on scheduled basis.

## How to use

use the trait ***AsyncDBHelpingTrait*** in the models where you want them to be in async mode. It will override the create & save methods for the model.
- In case of save, if model doesn't exists than only it will be put in async mode.
- There are two methods available which you can define in you model, where you are using the AsyncDBHelpingTrait, ***beforeMoveToRedis*** & ***afterMoveToRedis***
- ***beforeMoveToRedis*** : As the name suggest, this function runs before moving the data from redis to DB. While running this function, model will be holding the same data as saved into redis.
- ***afterMoveToRedis*** : this function runs after moving the data from redis to DB. The model at this point will hold all the data as saved into redis and primary key value (id).

***Move data from redis to DB***:
- You can run the command ***move-to-db:redis*** periodically to move the data from redis to DB.
- The command receives an optional argument ***batch_size***, default set to 1000. The batch size is the number of records which will be moved from redis to DB in one go.
