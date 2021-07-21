
<?php


class dataBaseClass
{
    public function connectDB() {
        return connect();
    }

    public function selectQuery($query) {
        return select($query);
    }

    public function execQuery1($query) {
        return execQuery($query);
    }
}
