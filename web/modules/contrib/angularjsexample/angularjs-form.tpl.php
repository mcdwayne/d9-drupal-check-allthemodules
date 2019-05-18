<?php

/**
 * @file
 * Angular js example template to render a book block.
 */
?>
<div ng-app="myapp" ng-controller="bookViewCtrl">
  <table>
    <tr>
      <th colspan=2>Add a new book</th>
    </tr>
    <tr>
      <td>
        <label for="book-name">Book name:</label></td>
      <td><input type="text" name="book-name" ng-model="bookData.name" id="book-name"></td>
    </tr>
    <tr>
      <td><label for="book-price">Book price:</label></td>
      <td><input type="text" name="book-price" ng-model="bookData.price" id="book-price"></td>
    </tr>
    <tr>
      <td><label for="author-id">Author id:</label></td>
      <td><input type="text" name="author-id" ng-model="bookData.authorId" id="author-id"></td>
      <td><input type="hidden" name="tokenid" ng-model="bookData.tokenid"
                 id="tokenid" value=""></td>
    </tr>
    <tr>
      <td></td>
      <td>
        <button ng-click="addNewBook(bookData)">Add</button>
      </td>
    </tr>
    <tr>
      <td colspan=2>
        <div>
          <label>List of books</label>
          <ul>
            <li ng-repeat="x in books">
              {{ x.bookname }}
            </li>
          </ul>

        </div>
      </td>
    </tr>
  </table>
</div>
