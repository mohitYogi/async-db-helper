<p align="center"><h1>Async DB Helper</p>

<p align="center">
<!-- put badges here -->
</p>

## Introduction

Sometimes it happens that we use  either logger or some special models to log the user data, which is critical to collect but not so much that waste our precious DB connection & resource on it. Like Auditing records for a model.

To help in such scenario, you can use this package. This package stores the records which were meant for DB into redis & moves them to DB on scheduled basis.