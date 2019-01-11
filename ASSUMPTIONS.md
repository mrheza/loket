# Requirements
1. PHP 5.6
2. Framework flight php
3. Postman
4. mysql 5.7
5. Apache


# Create Event
1. parameter	: 
	- location_id (integer)
	- event_name (varchar, textfield)
	- startdate (date, format 'd-m-Y', datefield)
	- enddate (date, format 'd-m-Y', datefield)
2. Sebelum create event harus memilih location terlebih dahulu

# Create Location
1. parameter 	:
	- location (varchar, textfield)

# Create Ticket
1. parameter 	:
	- event_id (integer)
	- type (varchar, textfiled)
	- price (integer, numberbox)
	- quota (integer, numberbox)
2. Sebelum create ticket harus memilih event terlebih dahulu
3. Price merupakan harga untuk 1 tiket

# Purchase Ticket
1. parameter 	:
	- username (varchar, textfield)
	- email (email, emailfield)
	- address (varchar, textarea)
	- payment (varchar, combobox)
	- detail (json) => Contoh : [{"ticket_id":"1", "qty":"1"},{"ticket_id":"3", "qty":"2"}]
2. username merupakan nama lengkap user
3. payment method dipilih melalui combobox, diasumsikan sudah ada master payment method di database
4. sebelum input detail harus memilih event maka akan muncul list tiket sesuai event
5. event akan terkunci maka user hanya bisa memilih tiket pada event yang dipilih
6. ketika submit transaksi diasumsikan user langsung membayar tiket sehingga status transaksi 1 (dibayar)