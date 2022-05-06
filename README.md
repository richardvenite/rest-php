**Transfer cash between users**
----
  Returns json data about a transaction between two users.

* **URL**

  /api/user/transaction

* **Method:**

  `POST`
  
*  **URL Params**

    None

* **Data Params**

   **Required:**
 
   `payer`<br />
   ``string | User's CPF | Max of 11 character's ``  <br /><br />
   `payee` <br />
   ``string | Receive's CPF/CNPJ | Max of 14 character's ``  <br /><br />
   `value` <br />
   ``number | Value to transfer | Must be greater than 0 ``  <br /><br />
* **Success Response:**

  * **Code:** 200 <br />
    **Content:** `{ "value": "10.99","payer": "12345678900","payee":"43665923000175" }`

   <br />
* **Error Response:**

  * **Code:** 400 Bad Request <br />
    **Content:** `{ error : "payer": ["The payer must not be greater than 11  characters."] }`

  OR

  * **Code:** 500 Internal Server Error <br />
    **Content:** `{ error : "Unexpected error please try again." }`

    <br />
* **Sample Call:**

  ```javascript
    curl --request POST \
    --url 'http://localhost/api/user/transaction' \
    --header 'Content-Type: application/json' \
    --data '{
        "payer": "12345678909",
        "payee": "12345678900",
        "value": 8.50
    }'
  ```