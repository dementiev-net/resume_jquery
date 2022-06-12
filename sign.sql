create table resume_sign
(
    id            int auto_increment
        primary key,
    filename      varchar(50)                           null,
    note          varchar(250)                          null,
    filesize      varchar(20)                           null,
    hash          varchar(50)                           null,
    sms_count     int(3)    default 0                   null,
    sms_date      timestamp                             null,
    docdate       timestamp default current_timestamp() null,
    sms_code      varchar(5)                            null,
    mobile        varchar(20)                           null,
    sms_att_count int(3)                                null
);

INSERT INTO resume_sign (id, filename, note, filesize, hash, sms_count, sms_date, docdate, sms_code, mobile, sms_att_count) VALUES (2423, 'file.doc', 'описание файла', '134 кб', 'HY676GHU89KI88JJ77', 1, null, '2021-12-29 14:39:19', '1234', '+89991234567', 5);